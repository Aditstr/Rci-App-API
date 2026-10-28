<?php

declare(strict_types=1);

use App\Services\AiQuotaService;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

const QUOTA_TEST_DATABASE = 'rci_quota_test';
const QUOTA_TEST_HOST = '127.0.0.1';
const QUOTA_TEST_PORT = '5432';
const QUOTA_TEST_NOW = '2026-10-28 10:00:00';

/**
 * Connection for the disposable local PostgreSQL target.
 *
 * Credentials come from QUOTA_PG_USER / QUOTA_PG_PASSWORD only. They are
 * never read from .env and never printed.
 *
 * @return array<string, mixed>
 */
function quota_test_connection(): array
{
    return [
        'driver' => 'pgsql',
        'host' => QUOTA_TEST_HOST,
        'port' => QUOTA_TEST_PORT,
        'database' => QUOTA_TEST_DATABASE,
        'username' => getenv('QUOTA_PG_USER') ?: 'postgres',
        'password' => getenv('QUOTA_PG_PASSWORD') ?: '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
        'options' => [PDO::ATTR_EMULATE_PREPARES => false],
    ];
}

function quota_test_boot(): Application
{
    $root = dirname(__DIR__, 2);
    $connection = quota_test_connection();

    // Set before Laravel loads .env; the immutable Dotenv repository keeps these.
    $overrides = [
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => QUOTA_TEST_HOST,
        'DB_PORT' => QUOTA_TEST_PORT,
        'DB_DATABASE' => QUOTA_TEST_DATABASE,
        'DB_USERNAME' => $connection['username'],
        'DB_PASSWORD' => $connection['password'],
        'DB_URL' => '',
        'CACHE_STORE' => 'database',
        'APP_DEBUG' => 'false',
        'GEMINI_API_KEY' => '',
    ];

    foreach ($overrides as $key => $value) {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    require $root . '/vendor/autoload.php';

    $app = require $root . '/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    config([
        'database.connections.quota_test' => $connection,
        'database.default' => 'quota_test',
        'cache.default' => 'database',
        'cache.stores.database' => [
            'driver' => 'database',
            'connection' => 'quota_test',
            'table' => 'cache',
            'lock_connection' => 'quota_test',
            'lock_table' => 'cache_locks',
        ],
    ]);

    DB::purge('quota_test');
    $app['cache']->forgetDriver('database');

    return $app;
}

/**
 * Abort before any write unless the live target is the disposable local database.
 *
 * @return array{driver: string, database: string, server: string, cache: string}
 */
function quota_test_guard(): array
{
    $fail = static function (string $reason): never {
        fwrite(STDERR, 'ABORT: ' . $reason . PHP_EOL);
        exit(1);
    };

    if (PHP_SAPI !== 'cli') {
        $fail('harness hanya boleh dijalankan dari CLI.');
    }

    if (! extension_loaded('pdo_pgsql')) {
        $fail('ekstensi pdo_pgsql tidak tersedia.');
    }

    if (app()->isProduction()) {
        $fail('APP_ENV=production tidak diizinkan.');
    }

    if (config('database.connections.quota_test.host') !== QUOTA_TEST_HOST) {
        $fail('host database pengujian bukan 127.0.0.1.');
    }

    $connection = DB::connection('quota_test');
    $driver = $connection->getDriverName();

    try {
        $target = $connection->selectOne(
            'select current_database() as database, coalesce(host(inet_server_addr()), \'local\') as server'
        );
    } catch (Throwable) {
        $fail('tidak bisa terhubung ke ' . QUOTA_TEST_DATABASE . ' di ' . QUOTA_TEST_HOST . ':' . QUOTA_TEST_PORT . '.');
    }

    $database = (string) ($target->database ?? '');
    $server = (string) ($target->server ?? '');

    $cache = Cache::store('database')->getStore();
    $cacheConnection = config('cache.stores.database.connection');

    if ($driver !== 'pgsql') {
        $fail('driver database harus pgsql, ditemukan [' . $driver . '].');
    }

    if ($database !== QUOTA_TEST_DATABASE) {
        $fail('database aktif harus ' . QUOTA_TEST_DATABASE . ', ditemukan [' . $database . '].');
    }

    if (! in_array($server, ['127.0.0.1', '::1', 'local'], true)) {
        $fail('server PostgreSQL bukan lokal, ditemukan [' . $server . '].');
    }

    if (! $cache instanceof DatabaseStore) {
        $fail('cache store harus database, ditemukan [' . $cache::class . '].');
    }

    if ($cacheConnection !== 'quota_test') {
        $fail('cache database harus memakai koneksi quota_test.');
    }

    if (config('database.default') !== 'quota_test') {
        $fail('koneksi default harus quota_test.');
    }

    if (Cache::getDefaultDriver() !== 'database') {
        $fail('cache default harus database.');
    }

    return [
        'driver' => $driver,
        'database' => $database,
        'server' => $server,
        'cache' => $cache::class,
    ];
}

function quota_test_key(int $userId): string
{
    return 'ai_quota:' . now(AiQuotaService::TIMEZONE)->toDateString() . ':user:' . $userId;
}
