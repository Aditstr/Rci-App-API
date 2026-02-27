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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('expert_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('category', [
                'corporate',
                'criminal',
                'family',
                'property',
                'labor',
                'immigration',
                'intellectual_property',
                'tax',
                'general',
            ])->default('general');
            $table->enum('status', [
                'submitted',
                'ai_analyzing',
                'bidding',
                'active',
                'completed',
                'cancelled',
                'dispute',
            ])->default('submitted');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->unsignedTinyInteger('ai_complexity_score')->nullable(); // 1-10
            $table->decimal('ai_estimated_cost', 15, 2)->nullable();
            $table->jsonb('ai_review_result')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('expert_id');
            $table->index('status');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
