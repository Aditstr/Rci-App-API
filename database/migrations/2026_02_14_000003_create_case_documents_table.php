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
        Schema::create('case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 50); // e.g. "application/pdf"
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->enum('document_type', [
                'evidence',
                'legal_letter',
                'contract',
                'ai_draft',
                'identification',
                'other',
            ])->default('other');
            $table->enum('ai_review_status', [
                'pending',
                'processing',
                'completed',
                'failed',
            ])->nullable();
            $table->jsonb('ai_review_result')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('case_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_documents');
    }
};
