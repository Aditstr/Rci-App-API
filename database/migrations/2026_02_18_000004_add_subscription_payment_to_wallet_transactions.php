<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'subscription_payment' to wallet_transactions type enum.
     *
     * Laravel uses CHECK constraints for enum columns on PostgreSQL.
     * We drop the old constraint and recreate it with the new value.
     */
    public function up(): void
    {
        // Drop existing check constraint and recreate with the new value
        DB::statement("ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS wallet_transactions_type_check");
        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_type_check CHECK (type::text = ANY (ARRAY['deposit','withdrawal','escrow_hold','payment_release','admin_fee','refund','subscription_payment']::text[]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS wallet_transactions_type_check");
        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_type_check CHECK (type::text = ANY (ARRAY['deposit','withdrawal','escrow_hold','payment_release','admin_fee','refund']::text[]))");
    }
};
