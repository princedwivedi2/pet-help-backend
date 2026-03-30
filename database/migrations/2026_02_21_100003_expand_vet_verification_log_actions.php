<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        // Expand vet_verification_logs action enum to include all legacy values
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated','approval_blocked','applied','request_more_info') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        
        // Defensively update values that would be truncated by the ENUM narrowing.
        DB::table('vet_verification_logs')
            ->whereIn('action', ['approval_blocked', 'applied', 'request_more_info'])
            ->update(['action' => 'rejected']);

        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");
    }
};
