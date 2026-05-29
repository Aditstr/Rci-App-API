<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sync the cases.status CHECK constraint with all statuses used in the codebase.
 *
 * Existing:  submitted, ai_analyzing, bidding, active, completed, cancelled, dispute
 * Adding:    pending, assigned, in_progress, reviewing, escalated
 */
return new class extends Migration
{
    private const ALL_STATUSES = [
        'submitted',
        'pending',
        'ai_analyzing',
        'assigned',
        'bidding',
        'active',
        'in_progress',
        'reviewing',
        'escalated',
        'completed',
        'cancelled',
        'dispute',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $values = implode(',', array_map(fn ($s) => "'{$s}'", self::ALL_STATUSES));

            DB::statement('ALTER TABLE cases DROP CONSTRAINT IF EXISTS cases_status_check');
            DB::statement(
                "ALTER TABLE cases ADD CONSTRAINT cases_status_check CHECK (status::text = ANY (ARRAY[{$values}]::text[]))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $original = "'submitted','ai_analyzing','bidding','active','completed','cancelled','dispute'";

            DB::statement('ALTER TABLE cases DROP CONSTRAINT IF EXISTS cases_status_check');
            DB::statement(
                "ALTER TABLE cases ADD CONSTRAINT cases_status_check CHECK (status::text = ANY (ARRAY[{$original}]::text[]))"
            );
        }
    }
};
