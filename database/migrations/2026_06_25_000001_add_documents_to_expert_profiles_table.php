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
        Schema::table('expert_profiles', function (Blueprint $table) {
            // ── Document paths ──────────────────────────────────
            $table->string('ktp_path')->nullable()->after('bio');
            $table->string('ijazah_path')->nullable()->after('ktp_path');
            $table->string('license_card_path')->nullable()->after('ijazah_path');   // wajib lawyer
            $table->string('cv_path')->nullable()->after('license_card_path');        // opsional lawyer
            $table->string('selfie_path')->nullable()->after('cv_path');              // wajib lawyer

            // ── Verification workflow ───────────────────────────
            $table->string('verification_status')->default('pending')->after('selfie_path');  // pending | approved | rejected
            $table->text('rejection_reason')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('rejection_reason');

            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_profiles', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'ktp_path',
                'ijazah_path',
                'license_card_path',
                'cv_path',
                'selfie_path',
                'verification_status',
                'rejection_reason',
                'verified_at',
            ]);
        });
    }
};
