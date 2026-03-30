<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        // Expand vet_verification_logs action enum to include suspend & reactivate
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected') NOT NULL");
    }
};
