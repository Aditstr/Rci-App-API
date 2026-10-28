<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use RuntimeException;

/**
 * Daily AI quota for RCI members.
 *
 * Membership comes from an active subscription (see User::hasActiveSubscription).
 * The day boundary is Asia/Jakarta midnight, independent of app.timezone.
 */
class AiQuotaService
{
    public const TIMEZONE = 'Asia/Jakarta';

    private const KEY_PREFIX = 'ai_quota';
    private const LOCK_SECONDS = 5;
    private const LOCK_WAIT_SECONDS = 3;
    private const MODE_ATOMIC = 'atomic';
    private const MODE_LOCK = 'lock';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function isMember(?User $user): bool
    {
        return $user !== null && $user->hasActiveSubscription();
    }

    public function limit(): int
    {
        return MembershipConfig::aiDailyLimit();
    }

    /**
     * Consume one question atomically.
     *
     * @return array{allowed: bool, reason: string|null, limit: int, used: int, remaining: int, key: string|null}
     */
    public function consume(?User $user): array
    {
        $limit = $this->limit();

        if ($user === null) {
            return $this->denied('guest', $limit);
        }

        if (! $this->isMember($user)) {
            return $this->denied('not_member', $limit);
        }

        if ($limit <= 0) {
            return $this->denied('quota_exhausted', $limit);
        }

        $key = $this->key($user);
        $used = $this->increment($key);

        if ($used > $limit) {
            $this->decrement($key);

            return [
                'allowed' => false,
                'reason' => 'quota_exhausted',
                'limit' => $limit,
                'used' => $limit,
                'remaining' => 0,
                'key' => $key,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit - $used,
            'key' => $key,
        ];
    }

    /**
     * Give a consumed question back, using the exact key it was taken from.
     *
     * @param  array{limit: int, key: string|null}  $consumption
     * @return array{limit: int, used: int, remaining: int}
     */
    public function refund(array $consumption): array
    {
        $key = $consumption['key'];
        $limit = $consumption['limit'];

        if ($key !== null) {
            $this->decrement($key);
        }

        $used = $key === null ? 0 : max(0, (int) $this->cache->get($key, 0));

        return [
            'limit' => $limit,
            'used' => min($used, $limit),
            'remaining' => max(0, $limit - $used),
        ];
    }

    /**
     * @return array{limit: int, used: int, remaining: int}
     */
    public function snapshot(?User $user): array
    {
        $limit = $this->limit();

        if ($user === null || ! $this->isMember($user)) {
            return ['limit' => $limit, 'used' => 0, 'remaining' => 0];
        }

        $used = min($limit, max(0, (int) $this->cache->get($this->key($user), 0)));

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
        ];
    }

    private function key(User $user): string
    {
        return self::KEY_PREFIX . ':' . now(self::TIMEZONE)->toDateString() . ':user:' . $user->id;
    }

    private function ttlSeconds(): int
    {
        $endOfDay = now(self::TIMEZONE)->endOfDay();

        return max(1, (int) ceil(now()->diffInSeconds($endOfDay, true)));
    }

    private function increment(string $key): int
    {
        $ttl = $this->ttlSeconds();

        if ($this->storageMode() === self::MODE_ATOMIC) {
            $this->cache->add($key, 0, $ttl);
            $used = $this->cache->increment($key);

            if ($used === false) {
                // The key expired between add() and increment(); recreate it once.
                $this->cache->add($key, 0, $ttl);
                $used = $this->cache->increment($key);
            }

            if ($used === false) {
                throw new RuntimeException('Gagal menaikkan kuota AI.');
            }

            return (int) $used;
        }

        return $this->cache->lock($key . ':lock', self::LOCK_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, function () use ($key, $ttl): int {
                $used = (int) $this->cache->get($key, 0) + 1;

                // A store may accept put() but not persist it; verify before allowing AI.
                if (! $this->cache->put($key, $used, $ttl) || (int) $this->cache->get($key, 0) !== $used) {
                    throw new RuntimeException('Gagal menyimpan kuota AI.');
                }

                return $used;
            });
    }

    private function decrement(string $key): void
    {
        if ($this->storageMode() === self::MODE_ATOMIC) {
            // Repository::decrement() returns int|false; 0 is a valid result.
            if ($this->cache->decrement($key) === false) {
                throw new RuntimeException('Gagal mengembalikan kuota AI.');
            }

            return;
        }

        // Same lock as increment() so a refund cannot race a consume on file/array stores.
        $this->cache->lock($key . ':lock', self::LOCK_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, function () use ($key): void {
                if ((int) $this->cache->get($key, 0) > 0 && $this->cache->decrement($key) === false) {
                    throw new RuntimeException('Gagal mengembalikan kuota AI.');
                }
            });
    }

    /**
     * Resolve a store that can actually keep the counter.
     *
     * Only atomic drivers and the real FileStore lock are accepted. NullStore
     * locks are no-ops, and ArrayStore is per-process, so both fail closed
     * instead of granting AI access.
     */
    private function storageMode(): string
    {
        $store = $this->cache->getStore();

        if ($store instanceof NullStore) {
            throw new RuntimeException('NullStore tidak menyimpan kuota AI.');
        }

        if ($store instanceof DatabaseStore
            || $store instanceof RedisStore
            || $store instanceof MemcachedStore
            || $store instanceof DynamoDbStore) {
            return self::MODE_ATOMIC;
        }

        if ($store instanceof FileStore) {
            return self::MODE_LOCK;
        }

        if ($store instanceof ArrayStore) {
            if (app()->environment('testing')) {
                return self::MODE_LOCK;
            }

            throw new RuntimeException('ArrayStore tidak aman untuk kuota AI di luar testing.');
        }

        throw new RuntimeException('Cache store [' . $store::class . '] tidak didukung untuk kuota AI.');
    }

    /**
     * @return array{allowed: bool, reason: string, limit: int, used: int, remaining: int, key: null}
     */
    private function denied(string $reason, int $limit): array
    {
        return [
            'allowed' => false,
            'reason' => $reason,
            'limit' => $limit,
            'used' => 0,
            'remaining' => 0,
            'key' => null,
        ];
    }
}
