<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE vet_verifications MODIFY COLUMN action ENUM('applied','approved','rejected','suspended','reactivated','approval_blocked','request_more_info') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE vet_verifications MODIFY COLUMN action ENUM('applied','approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");
    }
};
