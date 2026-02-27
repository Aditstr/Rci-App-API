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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('type', [
                'deposit',
                'withdrawal',
                'escrow_hold',
                'payment_release',
                'admin_fee',
                'refund',
            ]);
            $table->nullableMorphs('reference'); // reference_id + reference_type (Case, Subscription, etc.)
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
