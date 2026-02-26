<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 5 — Fix destructive enum override on vet_verification_logs.action.
 *
 * Migration 2026_02_25_100002 overwrote the enum without including 'applied'
 * (which was added by 2026_02_24_100001). This restores all valid values.
 *
 * Current (broken):  'approved','rejected','suspended','reactivated','approval_blocked'
 * Correct (fixed):   'applied','approved','rejected','suspended','reactivated','approval_blocked'
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('applied','approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");
    }
};
