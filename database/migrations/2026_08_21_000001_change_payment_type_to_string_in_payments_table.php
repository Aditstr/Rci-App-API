<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the check constraint created by Laravel's enum() to allow any string
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_type_check");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Note: Reverting string back to enum might fail if values other than the original enum are present.
            // But we specify it for consistency.
            // $table->enum('payment_type', ['case_payment', 'subscription'])->change();
        });
    }
};
