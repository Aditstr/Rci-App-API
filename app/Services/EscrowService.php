<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LegalCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EscrowService
{
    /**
     * Platform fee percentage (10%).
     */
    private const PLATFORM_FEE_PERCENT = 10;

    // ──────────────────────────────────────────────────────────────
    // 1. TOP-UP  — Add funds to a user's wallet
    // ──────────────────────────────────────────────────────────────

    /**
     * Increase a user's wallet balance and record a deposit transaction.
     *
     * Flow:
     *  1. Resolve (or create) the user's wallet.
     *  2. Credit the amount to the wallet balance.
     *  3. Record a `deposit` transaction with status `success`.
     *
     * Everything runs inside DB::transaction for atomicity.
     *
     * @param  User   $user    The user topping up
     * @param  float  $amount  Amount in IDR (must be > 0)
     * @return WalletTransaction  The recorded deposit transaction
     *
     * @throws RuntimeException if amount is invalid
     */
    public function topUp(User $user, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Jumlah top-up harus lebih dari 0.');
        }

        $amountStr = number_format($amount, 2, '.', '');

        return DB::transaction(function () use ($user, $amountStr) {

            // Resolve or create wallet, then lock it
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => '0.00'],
            );
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Credit balance
            $wallet->credit($amountStr);

            // Record transaction
            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $amountStr,
                'type'           => 'deposit',
                'status'         => 'success',
                'description'    => "Top-up saldo sebesar Rp " . number_format((float) $amountStr, 0, ',', '.'),
            ]);
        });
    }

    // ──────────────────────────────────────────────────────────────
    // 2. LOCK FUNDS FOR CASE  — Escrow hold
    // ──────────────────────────────────────────────────────────────

    /**
     * Deduct funds from the client's wallet and hold them in escrow.
     *
     * Flow:
     *  1. Validate amount > 0.
     *  2. Resolve the client's wallet (lock for update).
     *  3. Check sufficient balance → throw if not enough.
     *  4. Debit the amount from the wallet.
     *  5. Record an `escrow_hold` transaction with status `pending`.
     *  6. Update the case status to `active`.
     *
     * @param  LegalCase  $case    The case being funded
     * @param  float      $amount  Amount to lock in escrow (IDR)
     * @return WalletTransaction   The escrow_hold transaction record
     *
     * @throws RuntimeException if balance insufficient or amount invalid
     */
    public function lockFundsForCase(LegalCase $case, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Jumlah escrow harus lebih dari 0.');
        }

        $amountStr = number_format($amount, 2, '.', '');

        // The client who submitted the case pays
        $client = $case->client;

        if (! $client) {
            throw new RuntimeException('Kasus ini belum memiliki client yang terdaftar.');
        }

        return DB::transaction(function () use ($client, $case, $amountStr) {

            // Lock the client's wallet
            $wallet = Wallet::where('user_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException(
                    "User {$client->name} belum memiliki wallet. Silakan top-up terlebih dahulu."
                );
            }

            // Check balance (throws RuntimeException via Wallet::debit if insufficient)
            if (bccomp($wallet->balance, $amountStr, 2) < 0) {
                throw new RuntimeException(
                    "Saldo tidak mencukupi. Saldo saat ini: Rp " . number_format((float) $wallet->balance, 0, ',', '.')
                    . ", dibutuhkan: Rp " . number_format((float) $amountStr, 0, ',', '.') . "."
                );
            }

            // Debit from client wallet
            $wallet->debit($amountStr);

            // Record escrow_hold transaction (pending until case completed)
            $transaction = WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $amountStr,
                'type'           => 'escrow_hold',
                'reference_id'   => $case->id,
                'reference_type' => LegalCase::class,
                'status'         => 'pending',
                'description'    => "Escrow hold untuk kasus #{$case->case_number} sebesar Rp "
                                  . number_format((float) $amountStr, 0, ',', '.'),
            ]);

            // Activate the case
            $case->update(['status' => 'active']);

            return $transaction;
        });
    }

    // ──────────────────────────────────────────────────────────────
    // 3. RELEASE FUNDS  — Pay expert + platform fee
    // ──────────────────────────────────────────────────────────────

    /**
     * Release escrowed funds for a completed case.
     *
     * Flow:
     *  1. Validate case status is 'completed'.
     *  2. Find the pending `escrow_hold` transaction for this case.
     *  3. Calculate split: 90% expert payout, 10% platform fee.
     *  4. Credit 90% to the expert's wallet → record `payment_release`.
     *  5. Credit 10% to the admin's wallet → record `admin_fee`.
     *  6. Mark original escrow_hold as `success`.
     *
     * Uses DB::transaction + lockForUpdate for data integrity.
     *
     * @throws RuntimeException if validation fails
     */
    public function releaseFunds(LegalCase $case): void
    {
        // 1. Validate case status
        if ($case->status !== 'completed') {
            throw new RuntimeException(
                "Dana hanya bisa dicairkan untuk kasus berstatus 'completed'. Status saat ini: {$case->status}"
            );
        }

        // Resolve expert (validasi sebelum masuk transaction)
        $expert = $case->expert;

        if (! $expert) {
            throw new RuntimeException('Kasus ini belum memiliki mitra (expert) yang ditugaskan.');
        }

        $adminUser = User::where('role', 'admin')->firstOrFail();

        DB::transaction(function () use ($expert, $adminUser, $case) {

            // 2. Find & LOCK the pending escrow_hold transaction (prevents double release)
            $escrowTransaction = WalletTransaction::where('type', 'escrow_hold')
                ->where('status', 'pending')
                ->where('reference_type', LegalCase::class)
                ->where('reference_id', $case->id)
                ->lockForUpdate()
                ->firstOrFail();

            $totalAmount = $escrowTransaction->amount;

            // 3. Calculate fee split
            $adminFee    = bcdiv(bcmul($totalAmount, (string) self::PLATFORM_FEE_PERCENT, 2), '100', 2);
            $mitraPayout = bcsub($totalAmount, $adminFee, 2);

            // Lock both wallets to prevent race conditions
            $expertWallet = Wallet::where('user_id', $expert->id)->lockForUpdate()->firstOrFail();
            $adminWallet  = Wallet::where('user_id', $adminUser->id)->lockForUpdate()->firstOrFail();

            // 4. Credit 90% to expert/mitra wallet
            $expertWallet->credit($mitraPayout);

            WalletTransaction::create([
                'wallet_id'      => $expertWallet->id,
                'amount'         => $mitraPayout,
                'type'           => 'payment_release',
                'reference_id'   => $case->id,
                'reference_type' => LegalCase::class,
                'status'         => 'success',
                'description'    => "Pencairan dana kasus #{$case->case_number} — 90% (Rp "
                                  . number_format((float) $mitraPayout, 0, ',', '.') . ")",
            ]);

            // 5. Credit 10% to admin/platform wallet (RCI Revenue)
            $adminWallet->credit($adminFee);

            WalletTransaction::create([
                'wallet_id'      => $adminWallet->id,
                'amount'         => $adminFee,
                'type'           => 'admin_fee',
                'reference_id'   => $case->id,
                'reference_type' => LegalCase::class,
                'status'         => 'success',
                'description'    => "Platform fee kasus #{$case->case_number} — 10% (Rp "
                                  . number_format((float) $adminFee, 0, ',', '.') . ")",
            ]);

            // 6. Mark original escrow_hold as completed
            $escrowTransaction->update(['status' => 'success']);
        });
    }

    // ──────────────────────────────────────────────────────────────
    // 4. MEMBERSHIP UPGRADE — Potong saldo buat jadi PRO
    // ──────────────────────────────────────────────────────────────

    /**
     * Upgrade user ke status PRO/Corporate dengan memotong saldo wallet.
     *
     * @param  User  $user
     * @return void
     *
     * @throws RuntimeException jika saldo tidak cukup
     */
    public function subscribePro(User $user): void
    {
        // Guard: jangan charge ulang jika sudah PRO
        if ($user->role === 'corporate') {
            throw new RuntimeException('User sudah berstatus PRO/Corporate.');
        }

        $price = 50000.00; // Harga Paket PRO
        $priceStr = number_format($price, 2, '.', '');

        DB::transaction(function () use ($user, $priceStr) {
            // Ambil & Kunci Wallet User
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet) {
                throw new RuntimeException("User belum memiliki wallet. Silakan top-up terlebih dahulu.");
            }

            // Cek Saldo (Pakai bccomp biar akurat seperti function lain)
            if (bccomp($wallet->balance, $priceStr, 2) < 0) {
                throw new RuntimeException(
                    "Saldo tidak cukup untuk upgrade PRO. Dibutuhkan: Rp " . number_format((float) $priceStr, 0, ',', '.')
                );
            }

            // 1. Potong Saldo
            $wallet->debit($priceStr);

            // 2. Catat Transaksi (pakai 'withdrawal' agar sesuai CHECK constraint di DB)
            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $priceStr,
                'type'           => 'withdrawal',
                'status'         => 'success',
                'reference_type' => 'subscription',
                'reference_id'   => $user->id,
                'description'    => 'Pembayaran Membership PRO',
            ]);

            // 3. Update Status User
            $user->role = 'corporate';
            $user->save();
        });
    }
}