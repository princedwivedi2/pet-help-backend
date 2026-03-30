<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            // ENUM ALTER syntax below is MySQL-specific; skip for other drivers.
            return;
        }

        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Before narrowing the ENUM, update any 'approval_blocked' values to a value that will remain valid.
        DB::table('vet_verification_logs')->where('action', 'approval_blocked')->update(['action' => 'rejected']);

        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");
    }
};
