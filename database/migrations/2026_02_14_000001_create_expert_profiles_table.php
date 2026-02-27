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
        Schema::create('expert_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('license_number')->unique();
            $table->jsonb('specialization_tags')->nullable(); // e.g. ["Pidana", "Perdata", "Kenotariatan"]
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('successful_cases_count')->default(0);
            $table->unsignedInteger('current_workload')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expert_profiles');
    }
};
