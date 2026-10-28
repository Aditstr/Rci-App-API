<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use App\Services\AiQuotaService;
use App\Services\MembershipConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/bootstrap.php';

const WORKER_COUNT = 10;

$app = quota_test_boot();
$target = quota_test_guard();

fwrite(STDOUT, sprintf(
    "Target aman: driver=%s database=%s server=%s cache=%s\n",
    $target['driver'],
    $target['database'],
    $target['server'],
    $target['cache'],
));

if (in_array('--check', $argv, true)) {
    fwrite(STDOUT, "Mode --check: tidak ada operasi tulis.\n");
    exit(0);
}

Carbon::setTestNow(QUOTA_TEST_NOW);

$userId = null;
$email = null;
$quotaKey = null;
$membershipCacheExisted = false;
$tempDir = null;
$processes = [];

$cleanup = static function () use (&$userId, &$email, &$quotaKey, &$membershipCacheExisted, &$tempDir, &$processes): void {
    foreach ($processes as $process) {
        if (is_resource($process['proc'])) {
            @proc_terminate($process['proc']);
        }

        foreach ($process['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                @fclose($pipe);
            }
        }

        if (is_resource($process['proc'])) {
            @proc_close($process['proc']);
        }
    }

    $processes = [];

    try {
        if ($quotaKey !== null) {
            Cache::forget($quotaKey);
            DB::connection('quota_test')->table('cache_locks')
                ->where('key', config('cache.prefix') . $quotaKey . ':lock')
                ->delete();
        }
    } catch (Throwable) {
        // Cleanup is best-effort; the original failure is reported by the caller.
    }

    try {
        if (! $membershipCacheExisted) {
            Cache::forget('membership.config');
        }
    } catch (Throwable) {
        // The cache table may not exist if migration failed.
    }

    try {
        if ($userId !== null) {
            DB::connection('quota_test')->table('subscriptions')->where('user_id', $userId)->delete();
            DB::connection('quota_test')->table('users')->where('id', $userId)->delete();
        } elseif ($email !== null) {
            DB::connection('quota_test')->table('users')->where('email', $email)->delete();
        }
    } catch (Throwable) {
        // The tables may not exist if migration failed.
    }

    if ($tempDir !== null && is_dir($tempDir)) {
        foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($tempDir);
    }
};

$fail = static function (string $reason) use ($cleanup): never {
    $cleanup();
    fwrite(STDERR, 'ABORT: ' . $reason . PHP_EOL);
    exit(1);
};

try {
    $migrationExit = Artisan::call('migrate', [
        '--database' => 'quota_test',
        '--force' => true,
    ]);

    if ($migrationExit !== 0) {
        fwrite(STDERR, Artisan::output());
        $fail('migrasi database pengujian gagal.');
    }

    fwrite(STDOUT, "Schema: migration pending pada quota_test sudah dijalankan.\n");

    $membershipCacheExisted = Cache::has('membership.config');
    $limit = MembershipConfig::aiDailyLimit();

    if ($limit !== 3) {
        $fail('limit AI pada database pengujian bukan 3 (ditemukan ' . $limit . ').');
    }

    $email = 'quota-concurrency-' . bin2hex(random_bytes(8)) . '@example.test';

    $user = User::factory()->create([
        'email' => $email,
        'role' => 'client',
        'is_active' => true,
    ]);

    $userId = $user->id;

    Subscription::create([
        'user_id' => $userId,
        'plan_name' => 'pro',
        'price' => '20000.00',
        'currency' => 'IDR',
        'status' => 'active',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    if (! $user->hasActiveSubscription()) {
        $fail('subscription sintetis tidak aktif.');
    }

    $quotaKey = quota_test_key($userId);

    fwrite(STDOUT, "User sintetis: id={$userId} (dibersihkan setelah pengujian)\n");
    fwrite(STDOUT, "Menjalankan " . WORKER_COUNT . " worker...\n");

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rci-quota-' . getmypid() . '-' . bin2hex(random_bytes(4));

    if (! mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
        $fail('tidak bisa membuat direktori barrier.');
    }

    $goFile = $tempDir . DIRECTORY_SEPARATOR . 'go';
    $worker = __DIR__ . DIRECTORY_SEPARATOR . 'worker.php';
    $root = dirname(__DIR__, 2);

    for ($i = 1; $i <= WORKER_COUNT; $i++) {
        $readyFile = $tempDir . DIRECTORY_SEPARATOR . 'ready-' . $i;
        $args = [$userId, $readyFile, $goFile, QUOTA_TEST_NOW];

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg((string) $arg);
        }

        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );

        if (! is_resource($process)) {
            $fail('gagal menjalankan worker ' . $i . '.');
        }

        fclose($pipes[0]);

        $processes[] = [
            'proc' => $process,
            'pipes' => [1 => $pipes[1], 2 => $pipes[2]],
            'ready' => $readyFile,
        ];
    }

    $readyDeadline = microtime(true) + 60;

    while (true) {
        $ready = 0;
        foreach ($processes as $process) {
            if (is_file($process['ready'])) {
                $ready++;
            }
        }

        if ($ready === WORKER_COUNT) {
            break;
        }

        if (microtime(true) > $readyDeadline) {
            $fail('hanya ' . $ready . '/' . WORKER_COUNT . ' worker yang siap.');
        }

        usleep(50000);
    }

    $startAt = microtime(true) + 0.5;
    file_put_contents($goFile, (string) $startAt);

    $results = [];

    foreach ($processes as $index => $process) {
        $stdout = stream_get_contents($process['pipes'][1]);
        $stderr = stream_get_contents($process['pipes'][2]);
        fclose($process['pipes'][1]);
        fclose($process['pipes'][2]);

        $exitCode = proc_close($process['proc']);
        $processes[$index]['proc'] = null;

        $lines = preg_split('/\R/', trim((string) $stdout)) ?: [];
        $line = trim((string) end($lines));
        $result = json_decode($line, true);

        if ($exitCode !== 0 || ! is_array($result) || isset($result['error'])) {
            $detail = is_array($result) && isset($result['error'])
                ? $result['error']
                : (trim((string) $stderr) ?: 'exit code ' . $exitCode);

            $fail('worker ' . ($index + 1) . ' gagal: ' . $detail);
        }

        $results[] = $result;
    }

    fwrite(STDOUT, "\n");
    fwrite(STDOUT, sprintf("%-3s %-8s %-8s %-18s %-5s %s\n", '#', 'PID', 'Allowed', 'Reason', 'Used', 'Remaining'));

    foreach ($results as $index => $result) {
        fwrite(STDOUT, sprintf(
            "%-3d %-8s %-8s %-18s %-5s %s\n",
            $index + 1,
            $result['pid'] ?? '-',
            $result['allowed'] ? 'yes' : 'no',
            $result['reason'] ?? '-',
            $result['used'] ?? '-',
            $result['remaining'] ?? '-',
        ));
    }

    $accepted = count(array_filter($results, static fn (array $result): bool => $result['allowed'] === true));
    $denied = count($results) - $accepted;
    $exhausted = count(array_filter(
        $results,
        static fn (array $result): bool => $result['allowed'] === false && $result['reason'] === 'quota_exhausted'
    ));
    $counter = (int) Cache::get($quotaKey, 0);

    $pass = $accepted === 3 && $denied === 7 && $exhausted === 7 && $counter === 3;

    fwrite(STDOUT, "\n");
    fwrite(STDOUT, "Accepted: {$accepted}\n");
    fwrite(STDOUT, "Denied:   {$denied} (quota_exhausted: {$exhausted})\n");
    fwrite(STDOUT, "Counter:  {$counter}\n");
    fwrite(STDOUT, "Provider AI: 0 panggilan (harness hanya memanggil AiQuotaService::consume).\n");

    $cleanup();

    $userRemaining = DB::connection('quota_test')->table('users')->where('email', $email)->count();
    $cacheRemaining = Cache::has($quotaKey);
    $clean = $userRemaining === 0 && ! $cacheRemaining;

    fwrite(STDOUT, 'Cleanup: user=' . ($userRemaining === 0 ? 'dihapus' : 'MASIH ADA')
        . ', cache=' . ($cacheRemaining ? 'MASIH ADA' : 'dihapus') . "\n");

    $pass = $pass && $clean;

    fwrite(STDOUT, $pass ? "RESULT: PASS\n" : "RESULT: FAIL\n");

    exit($pass ? 0 : 1);
} catch (Throwable $e) {
    $fail(get_class($e) . ': ' . $e->getMessage());
}
