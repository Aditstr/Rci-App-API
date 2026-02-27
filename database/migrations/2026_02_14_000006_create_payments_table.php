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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->enum('payment_type', ['case_payment', 'subscription']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'refunded',
            ])->default('pending');
            $table->string('payment_method')->nullable(); // e.g. "bank_transfer", "e_wallet", "credit_card"
            $table->string('payment_gateway_ref')->nullable(); // reference from payment gateway
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('metadata')->nullable(); // extra data from payment gateway
            $table->timestamps();

            $table->index('user_id');
            $table->index('case_id');
            $table->index('subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
