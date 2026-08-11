<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add Xendit-specific columns to the payments table for
     * tracking invoice references and payment URLs.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('xendit_invoice_id')->nullable()->after('payment_gateway_ref')
                ->comment('Xendit Invoice ID (e.g., 579c8d61f23fa4ca35e52da4)');
            $table->text('xendit_invoice_url')->nullable()->after('xendit_invoice_id')
                ->comment('Xendit Invoice payment URL for the user');
            $table->timestamp('xendit_expiry_date')->nullable()->after('xendit_invoice_url')
                ->comment('When the Xendit Invoice expires');

            $table->index('xendit_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['xendit_invoice_id']);
            $table->dropColumn(['xendit_invoice_id', 'xendit_invoice_url', 'xendit_expiry_date']);
        });
    }
};
