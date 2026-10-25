<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wallet transaction types including subscription_payment.
     */
    private const TYPES = [
        'deposit',
        'withdrawal',
        'escrow_hold',
        'payment_release',
        'admin_fee',
        'refund',
        'subscription_payment',
    ];

    public function up(): void
    {
        // PostgreSQL already allows subscription_payment via 2026_02_18_000004.
        if (DB::getDriverName() === 'pgsql') {
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();

            // SQLite rebuilds the table for change(); re-declare status so its
            // CHECK constraint is not silently dropped.
            $table->enum('status', ['pending', 'success', 'failed'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        // PostgreSQL already allows subscription_payment via 2026_02_18_000004,
        // so this migration owns no schema change there.
        if (DB::getDriverName() === 'pgsql') {
            return;
        }

        if (DB::table('wallet_transactions')->where('type', 'subscription_payment')->exists()) {
            throw new RuntimeException(
                'Rollback dibatalkan: masih ada transaksi subscription_payment. '
                . 'Hapus atau arsipkan transaksi tersebut sebelum rollback.'
            );
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', array_slice(self::TYPES, 0, 6))->change();
            $table->enum('status', ['pending', 'success', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
