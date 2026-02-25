<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'approval_blocked' action to vet_verification_logs enum
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");
    }
};
