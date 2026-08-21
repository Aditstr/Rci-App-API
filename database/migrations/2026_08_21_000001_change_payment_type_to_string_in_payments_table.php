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
        // Add 'topup' to Postgres enum
        \Illuminate\Support\Facades\DB::statement("ALTER TYPE payments_payment_type_enum ADD VALUE IF NOT EXISTS 'topup'");
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
