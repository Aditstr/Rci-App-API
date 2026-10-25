<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    /**
     * Buy or extend the RCI membership using the user's wallet balance.
     *
     * Price and duration always come from MembershipConfig, never from the
     * request. Renewals continue from the current ends_at; expired or missing
     * memberships start now. Wallet debit, subscription, and ledger entry are
     * written in one transaction.
     *
     * A repeated request with the same key from the same user returns the
     * original subscription without a second debit. The key is stored on the
     * subscription inside the transaction, so a failed purchase leaves no key
     * behind.
     *
     * @throws RuntimeException when the key is missing or invalid, membership
     *                          is disabled, the wallet is missing, or the
     *                          balance is insufficient
     */
    public function purchase(User $user, ?string $idempotencyKey = null): Subscription
    {
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        $existing = $this->findByIdempotencyKey($user, $idempotencyKey);

        if ($existing !== null) {
            return $existing;
        }

        if (! MembershipConfig::enabled()) {
            throw new RuntimeException('Membership sedang tidak tersedia untuk dibeli.');
        }

        $price = MembershipConfig::price();
        $durationDays = MembershipConfig::durationDays();
        $priceStr = number_format($price, 2, '.', '');

        try {
            return DB::transaction(function () use ($user, $priceStr, $price, $durationDays, $idempotencyKey) {
                // A concurrent request with the same key may have committed
                // while this one waited for the wallet lock.
                $existing = $this->findByIdempotencyKey($user, $idempotencyKey);

                if ($existing !== null) {
                    return $existing;
                }

                // Lock first, then read the balance, so parallel purchases cannot
                // both spend the same funds.
                $wallet = Wallet::where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw new RuntimeException(
                        'Anda belum memiliki wallet. Silakan top-up terlebih dahulu.'
                    );
                }

                if (bccomp($wallet->balance, $priceStr, 2) < 0) {
                    $shortage = bcsub($priceStr, (string) $wallet->balance, 2);

                    throw new RuntimeException(
                        'Saldo tidak mencukupi untuk membeli membership. '
                        . 'Saldo saat ini: Rp ' . number_format((float) $wallet->balance, 0, ',', '.')
                        . ', dibutuhkan: Rp ' . number_format($price, 0, ',', '.')
                        . '. Silakan top-up minimal Rp ' . number_format((float) $shortage, 0, ',', '.') . '.'
                    );
                }

                $wallet->debit($priceStr);

                $startsAt = $this->nextStartAt($user);
                $endsAt = $startsAt->copy()->addDays($durationDays);

                $subscription = Subscription::create([
                    'user_id'         => $user->id,
                    'idempotency_key' => $idempotencyKey,
                    'plan_name'       => 'pro',
                    'price'           => $priceStr,
                    'currency'        => 'IDR',
                    'status'          => 'active',
                    'starts_at'       => $startsAt,
                    'ends_at'         => $endsAt,
                ]);

                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'amount'         => $priceStr,
                    'type'           => 'subscription_payment',
                    'reference_id'   => $subscription->id,
                    'reference_type' => Subscription::class,
                    'status'         => 'success',
                    'description'    => 'Pembayaran membership RCI ' . $durationDays . ' hari - Rp '
                                        . number_format($price, 0, ',', '.'),
                ]);

                return $subscription;
            });
        } catch (UniqueConstraintViolationException $e) {
            // The unique (user_id, idempotency_key) index rejected a racing
            // duplicate. Its transaction rolled back, so return the winner.
            $existing = $this->findByIdempotencyKey($user, $idempotencyKey);

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }

    private function findByIdempotencyKey(User $user, string $idempotencyKey): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function requireIdempotencyKey(?string $idempotencyKey): string
    {
        if ($idempotencyKey === null || $idempotencyKey === '') {
            throw new RuntimeException('Header Idempotency-Key wajib diisi.');
        }

        if (strlen($idempotencyKey) > 255) {
            throw new RuntimeException('Idempotency-Key terlalu panjang (maksimal 255 karakter).');
        }

        if (preg_match('/^[A-Za-z0-9._:-]+$/', $idempotencyKey) !== 1) {
            throw new RuntimeException('Format Idempotency-Key tidak valid.');
        }

        return $idempotencyKey;
    }

    /**
     * Start from the furthest active ends_at, otherwise from now.
     */
    private function nextStartAt(User $user): CarbonInterface
    {
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->lockForUpdate()
            ->first();

        return $activeSubscription ? $activeSubscription->ends_at->copy() : now();
    }

    /**
     * Get subscription status details for a user.
     */
    public function getStatus(User $user): array
    {
        $activeSub = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        if (! $activeSub) {
            return [
                'is_pro'      => false,
                'plan'        => 'free',
                'message'     => 'Anda belum berlangganan Pro.',
                'upgrade_url' => url('/pricing'),
                'price'       => 'Rp ' . number_format(MembershipConfig::price(), 0, ',', '.') . '/bulan',
            ];
        }

        $daysLeft = (int) now()->diffInDays($activeSub->ends_at);

        return [
            'is_pro'     => true,
            'plan'       => 'pro',
            'starts_at'  => $activeSub->starts_at->toIso8601String(),
            'ends_at'    => $activeSub->ends_at->toIso8601String(),
            'days_left'  => $daysLeft,
            'message'    => "Langganan Pro aktif. Sisa {$daysLeft} hari.",
            'auto_renew' => false,
        ];
    }
}
