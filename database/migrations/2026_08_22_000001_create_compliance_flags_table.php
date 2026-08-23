<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 60)->default('off_platform_payment');
            $table->string('source', 30);
            $table->string('severity', 20)->default('medium');
            $table->unsignedSmallInteger('risk_score')->default(0);
            $table->jsonb('matched_signals')->nullable();
            $table->text('evidence_text');
            $table->text('reporter_notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['subject_user_id', 'status']);
            $table->index(['case_id', 'created_at']);
            $table->index(['message_id', 'reporter_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_flags');
    }
};
