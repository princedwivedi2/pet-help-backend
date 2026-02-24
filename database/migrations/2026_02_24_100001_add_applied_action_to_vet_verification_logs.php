<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'applied' action to vet_verification_logs
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('applied','approved','rejected','suspended','reactivated') NOT NULL");

        // Make admin_id nullable for self-submitted applications (no admin involved)
        Schema::table('vet_verification_logs', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vet_verification_logs MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");

        Schema::table('vet_verification_logs', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable(false)->change();
        });
    }
};
