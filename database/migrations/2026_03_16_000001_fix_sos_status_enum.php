<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CRIT-02 FIX: The expand migration (2026_02_27_000004) overwrote the SOS status ENUM
 * and omitted 'sos_in_progress'. This migration re-adds all required statuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only run for MySQL — SQLite doesn't enforce ENUMs
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE sos_requests MODIFY COLUMN status ENUM(
                'pending', 'acknowledged', 'in_progress', 'completed', 'cancelled',
                'sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived',
                'sos_in_progress', 'treatment_in_progress', 'sos_completed', 'sos_cancelled', 'expired'
            ) DEFAULT 'sos_pending'");
        }
    }

    public function down(): void
    {
        // Revert to the previous (broken) state from expand migration
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE sos_requests MODIFY COLUMN status ENUM(
                'pending', 'acknowledged', 'in_progress', 'completed', 'cancelled',
                'sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived',
                'treatment_in_progress', 'sos_completed', 'sos_cancelled', 'expired'
            ) DEFAULT 'pending'");
        }
    }
};
