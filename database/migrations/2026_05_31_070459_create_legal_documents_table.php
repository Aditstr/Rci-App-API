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
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique(); // e.g., "Pasal 362 KUHP"
            $table->text('content'); // The actual text of the law
            $table->text('keywords')->nullable(); // Keywords for lexical matching
            $table->string('category')->index(); // e.g., 'pidana', 'perdata'
            $table->timestamps();
        });

        // Create a full-text search index for Postgres
        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('CREATE INDEX legal_documents_keywords_idx ON legal_documents USING gin(to_tsvector(\'indonesian\', keywords))');
            \Illuminate\Support\Facades\DB::statement('CREATE INDEX legal_documents_content_idx ON legal_documents USING gin(to_tsvector(\'indonesian\', content))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
