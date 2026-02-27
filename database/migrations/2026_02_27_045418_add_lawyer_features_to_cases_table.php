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
        Schema::table('cases', function (Blueprint $table) {
            // Marketplace: Is this case open for bidding?
            $table->boolean('is_marketplace')->default(false);

            // Quotation by Lawyer
            $table->decimal('proposed_fee', 15, 2)->nullable();
            $table->text('fee_notes')->nullable();

            // Structure & Fee Split (e.g. Fixed Fee, Success Fee)
            $table->jsonb('fee_structure')->nullable(); 

            // Track state for the quotation
            // null / 'pending', 'approved_by_client', 'rejected_by_client'
            $table->string('quotation_status')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'is_marketplace',
                'proposed_fee',
                'fee_notes',
                'fee_structure',
                'quotation_status'
            ]);
        });
    }
};
