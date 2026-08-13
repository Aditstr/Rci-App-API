<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add fields to support case completion flow:
 *  - completion_notes: Expert's summary/notes when marking case as done
 *  - expert_completed_at: Timestamp when expert marked as done
 *  - client_confirmed_at: Timestamp when client confirmed completion
 *  - dispute_reason: Client's reason for disputing
 *  - cancellation_reason: Client's reason for cancelling
 *  - New status 'awaiting_confirmation' in the enum
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->text('completion_notes')->nullable()->after('completed_at');
            $table->timestamp('expert_completed_at')->nullable()->after('completion_notes');
            $table->timestamp('client_confirmed_at')->nullable()->after('expert_completed_at');
            $table->text('dispute_reason')->nullable()->after('client_confirmed_at');
            $table->text('cancellation_reason')->nullable()->after('dispute_reason');
        });

        // Add 'awaiting_confirmation' to the status enum for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cases MODIFY COLUMN status ENUM(
                'submitted','pending','ai_analyzing','assigned','bidding',
                'active','in_progress','reviewing','escalated',
                'awaiting_confirmation','completed','cancelled','dispute'
            ) DEFAULT 'submitted'");
        }

        // Add 'awaiting_confirmation' to the status CHECK constraint for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            $statuses = [
                'submitted','pending','ai_analyzing','assigned','bidding',
                'active','in_progress','reviewing','escalated',
                'awaiting_confirmation','completed','cancelled','dispute',
            ];
            $values = implode(',', array_map(fn ($s) => "'{$s}'", $statuses));

            DB::statement('ALTER TABLE cases DROP CONSTRAINT IF EXISTS cases_status_check');
            DB::statement(
                "ALTER TABLE cases ADD CONSTRAINT cases_status_check CHECK (status::text = ANY (ARRAY[{$values}]::text[]))"
            );
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'completion_notes',
                'expert_completed_at',
                'client_confirmed_at',
                'dispute_reason',
                'cancellation_reason',
            ]);
        });

        // Revert status enum for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cases MODIFY COLUMN status ENUM(
                'submitted','pending','ai_analyzing','assigned','bidding',
                'active','in_progress','reviewing','escalated',
                'completed','cancelled','dispute'
            ) DEFAULT 'submitted'");
        }

        // Revert status CHECK constraint for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            $statuses = [
                'submitted','pending','ai_analyzing','assigned','bidding',
                'active','in_progress','reviewing','escalated',
                'completed','cancelled','dispute',
            ];
            $values = implode(',', array_map(fn ($s) => "'{$s}'", $statuses));

            DB::statement('ALTER TABLE cases DROP CONSTRAINT IF EXISTS cases_status_check');
            DB::statement(
                "ALTER TABLE cases ADD CONSTRAINT cases_status_check CHECK (status::text = ANY (ARRAY[{$values}]::text[]))"
            );
        }
    }
};
