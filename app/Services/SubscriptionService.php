<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    /**
     * Pro membership price in IDR.
     */
    private const PRO_PRICE = 50000;

    /**
     * Subscription duration in days.
     */
    private const DURATION_DAYS = 30;

    // ──────────────────────────────────────────────────────────────
    // SUBSCRIBE PRO — Purchase Pro membership via wallet
    // ──────────────────────────────────────────────────────────────

    /**
     * Upgrade a user to Pro membership using their wallet balance.
     *
     * Flow:
     *  1. Check if user already has an active subscription → prevent double-purchase.
     *  2. Resolve the user's wallet (lock for update).
     *  3. Validate sufficient balance (≥ Rp 50.000).
     *  4. Debit Rp 50.000 from wallet.
     *  5. Record `subscription_payment` transaction.
     *  6. Create a Subscription record (active, 30 days).
     *  7. All wrapped in DB::transaction for atomicity.
     *
     * @param  User  $user  The user upgrading to Pro
     * @return Subscription  The newly created subscription
     *
     * @throws RuntimeException if already subscribed, no wallet, or insufficient balance
     */
    public function subscribePro(User $user): Subscription
    {
        // 1. Prevent double-purchase
        if ($user->hasActiveSubscription()) {
            throw new RuntimeException(
                'Anda sudah memiliki langganan Pro yang masih aktif hingga '
                . $user->subscriptions()
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->first()
                    ?->ends_at->format('d M Y')
                . '.'
            );
        }

        $priceStr = number_format(self::PRO_PRICE, 2, '.', '');

        return DB::transaction(function () use ($user, $priceStr) {

            // 2. Lock the user's wallet
            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException(
                    'Anda belum memiliki wallet. Silakan top-up terlebih dahulu.'
                );
            }

            // 3. Check balance
            if (bccomp($wallet->balance, $priceStr, 2) < 0) {
                $shortage = bcsub($priceStr, $wallet->balance, 2);
                throw new RuntimeException(
                    'Saldo tidak mencukupi untuk berlangganan Pro. '
                    . 'Saldo saat ini: Rp ' . number_format((float) $wallet->balance, 0, ',', '.')
                    . ', dibutuhkan: Rp ' . number_format(self::PRO_PRICE, 0, ',', '.')
                    . '. Silakan top-up minimal Rp ' . number_format((float) $shortage, 0, ',', '.') . '.'
                );
            }

            // 4. Debit wallet
            $wallet->debit($priceStr);

            // 5. Record transaction
            $startsAt = now();
            $endsAt   = now()->addDays(self::DURATION_DAYS);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $priceStr,
                'type'           => 'subscription_payment',
                'reference_type' => Subscription::class,
                'status'         => 'success',
                'description'    => 'Pembayaran langganan Pro (30 hari) — Rp '
                                  . number_format(self::PRO_PRICE, 0, ',', '.'),
            ]);

            // 6. Create subscription
            $subscription = Subscription::create([
                'user_id'   => $user->id,
                'plan_name' => 'pro',
                'price'     => $priceStr,
                'currency'  => 'IDR',
                'status'    => 'active',
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
            ]);

            // Update the WalletTransaction reference_id now that we have the subscription ID
            WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'subscription_payment')
                ->whereNull('reference_id')
                ->latest()
                ->first()
                ?->update(['reference_id' => $subscription->id]);

            return $subscription;
        });
    }

    // ──────────────────────────────────────────────────────────────
    // RENEW PRO — Extend existing subscription
    // ──────────────────────────────────────────────────────────────

    /**
     * Renew/extend a user's Pro membership by another 30 days.
     *
     * - If subscription is still active → extends from current ends_at.
     * - If subscription expired → starts fresh from now().
     *
     * @param  User  $user
     * @return Subscription
     */
    public function renewPro(User $user): Subscription
    {
        $priceStr = number_format(self::PRO_PRICE, 2, '.', '');

        return DB::transaction(function () use ($user, $priceStr) {

            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException('Anda belum memiliki wallet.');
            }

            if (bccomp($wallet->balance, $priceStr, 2) < 0) {
                throw new RuntimeException(
                    'Saldo tidak mencukupi. Saldo: Rp ' . number_format((float) $wallet->balance, 0, ',', '.')
                    . ', dibutuhkan: Rp ' . number_format(self::PRO_PRICE, 0, ',', '.') . '.'
                );
            }

            $wallet->debit($priceStr);

            // Determine start: extend from current or start fresh
            $latestSub = $user->subscriptions()
                ->where('plan_name', 'pro')
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->orderByDesc('ends_at')
                ->first();

            $startsAt = $latestSub ? $latestSub->ends_at : now();
            $endsAt   = $startsAt->copy()->addDays(self::DURATION_DAYS);

            $subscription = Subscription::create([
                'user_id'   => $user->id,
                'plan_name' => 'pro',
                'price'     => $priceStr,
                'currency'  => 'IDR',
                'status'    => 'active',
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
            ]);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $priceStr,
                'type'           => 'subscription_payment',
                'reference_id'   => $subscription->id,
                'reference_type' => Subscription::class,
                'status'         => 'success',
                'description'    => 'Perpanjangan langganan Pro (+30 hari) — hingga '
                                  . $endsAt->format('d M Y'),
            ]);

            return $subscription;
        });
    }

    // ──────────────────────────────────────────────────────────────
    // CHECK STATUS — Helper
    // ──────────────────────────────────────────────────────────────

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
                'price'       => 'Rp ' . number_format(self::PRO_PRICE, 0, ',', '.') . '/bulan',
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
