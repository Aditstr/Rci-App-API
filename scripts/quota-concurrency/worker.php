<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AiQuotaService;
use Illuminate\Support\Carbon;

require __DIR__ . '/bootstrap.php';

$app = quota_test_boot();
quota_test_guard();

$userId = (int) ($argv[1] ?? 0);
$readyFile = (string) ($argv[2] ?? '');
$goFile = (string) ($argv[3] ?? '');
$now = (string) ($argv[4] ?? QUOTA_TEST_NOW);

$fail = static function (string $reason): never {
    echo json_encode(['error' => $reason], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
};

try {
    Carbon::setTestNow($now);

    $user = User::query()->find($userId);

    if ($user === null || ! $user->hasActiveSubscription()) {
        $fail('user sintetis tidak ditemukan atau tidak memiliki subscription aktif.');
    }

    if ($readyFile === '' || $goFile === '') {
        $fail('argumen barrier tidak lengkap.');
    }

    file_put_contents($readyFile, (string) getmypid());

    $deadline = microtime(true) + 60;

    while (! is_file($goFile)) {
        if (microtime(true) > $deadline) {
            $fail('barrier tidak pernah dibuka.');
        }

        usleep(1000);
    }

    $startAt = (float) file_get_contents($goFile);

    while (microtime(true) < $startAt) {
        usleep(100);
    }

    $result = $app->make(AiQuotaService::class)->consume($user);

    echo json_encode([
        'pid' => getmypid(),
        'allowed' => $result['allowed'],
        'reason' => $result['reason'],
        'used' => $result['used'],
        'remaining' => $result['remaining'],
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    $fail(get_class($e) . ': ' . $e->getMessage());
}
