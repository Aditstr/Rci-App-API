<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the RCI membership configuration.
 *
 * Values live in the existing `settings` table as four keys so admins can
 * change them without a deployment. Reads never write; missing or invalid
 * stored values fall back to the defaults below.
 */
class MembershipConfig
{
    public const PRICE_KEY = 'membership_price';
    public const DURATION_DAYS_KEY = 'membership_duration_days';
    public const AI_DAILY_LIMIT_KEY = 'membership_ai_daily_limit';
    public const ENABLED_KEY = 'membership_enabled';

    public const DEFAULT_PRICE = 20000;
    public const DEFAULT_DURATION_DAYS = 30;
    public const DEFAULT_AI_DAILY_LIMIT = 3;
    public const DEFAULT_ENABLED = true;

    public const MIN_PRICE = 1000;
    public const MAX_PRICE = 100000000;
    public const MIN_DURATION_DAYS = 1;
    public const MAX_DURATION_DAYS = 366;
    public const MIN_AI_DAILY_LIMIT = 0;
    public const MAX_AI_DAILY_LIMIT = 100;

    private const CACHE_KEY = 'membership.config';
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [
            self::PRICE_KEY,
            self::DURATION_DAYS_KEY,
            self::AI_DAILY_LIMIT_KEY,
            self::ENABLED_KEY,
        ];
    }

    /**
     * @return array{price: int, duration_days: int, ai_daily_limit: int, enabled: bool}
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, static function (): array {
            $stored = Setting::query()
                ->whereIn('key', self::keys())
                ->pluck('value', 'key');

            return [
                'price' => self::toInt($stored[self::PRICE_KEY] ?? null, self::DEFAULT_PRICE, self::MIN_PRICE, self::MAX_PRICE),
                'duration_days' => self::toInt($stored[self::DURATION_DAYS_KEY] ?? null, self::DEFAULT_DURATION_DAYS, self::MIN_DURATION_DAYS, self::MAX_DURATION_DAYS),
                'ai_daily_limit' => self::toInt($stored[self::AI_DAILY_LIMIT_KEY] ?? null, self::DEFAULT_AI_DAILY_LIMIT, self::MIN_AI_DAILY_LIMIT, self::MAX_AI_DAILY_LIMIT),
                'enabled' => self::toBool($stored[self::ENABLED_KEY] ?? null, self::DEFAULT_ENABLED),
            ];
        });
    }

    public static function price(): int
    {
        return self::all()['price'];
    }

    public static function durationDays(): int
    {
        return self::all()['duration_days'];
    }

    public static function aiDailyLimit(): int
    {
        return self::all()['ai_daily_limit'];
    }

    public static function enabled(): bool
    {
        return self::all()['enabled'];
    }

    /**
     * Persist a full configuration payload and drop the cached snapshot.
     *
     * @param  array{price: int|string, duration_days: int|string, ai_daily_limit: int|string, enabled: bool|int|string}  $data
     */
    public static function update(array $data): void
    {
        $values = [
            self::PRICE_KEY => (string) (int) $data['price'],
            self::DURATION_DAYS_KEY => (string) (int) $data['duration_days'],
            self::AI_DAILY_LIMIT_KEY => (string) (int) $data['ai_daily_limit'],
            self::ENABLED_KEY => filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
        ];

        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    private static function toInt(mixed $value, int $default, int $min, int $max): int
    {
        if (! is_string($value) && ! is_int($value)) {
            return $default;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d+$/', $value) !== 1) {
            return $default;
        }

        $int = (int) $value;

        return $int >= $min && $int <= $max ? $int : $default;
    }

    private static function toBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
