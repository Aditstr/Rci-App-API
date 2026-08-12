<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'topup' to the payment_type enum.
     *
     * Note: This migration uses raw SQL for PostgreSQL since
     * Laravel doesn't natively support ALTER TYPE for enums.
     */
    public function up(): void
    {
        // PostgreSQL only: SQLite doesn't support ALTER TABLE ... DROP CONSTRAINT
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_type_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_type_check CHECK (payment_type::text = ANY (ARRAY['case_payment'::text, 'subscription'::text, 'topup'::text]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_type_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_type_check CHECK (payment_type::text = ANY (ARRAY['case_payment'::text, 'subscription'::text]))");
        }
    }
};
