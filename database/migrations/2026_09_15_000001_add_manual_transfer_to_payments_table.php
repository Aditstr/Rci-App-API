<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('xendit_expiry_date');
            $table->string('bank_name')->nullable()->after('proof_path');
            $table->string('sender_name')->nullable()->after('bank_name');
            $table->foreignId('verified_by')->nullable()->after('sender_name')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('rejection_reason')->nullable()->after('verified_at');
        });

        // Drop old enum check if exists so new statuses work on Postgres
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'bank_name', 'sender_name', 'verified_by', 'verified_at', 'rejection_reason']);
        });
    }
};
