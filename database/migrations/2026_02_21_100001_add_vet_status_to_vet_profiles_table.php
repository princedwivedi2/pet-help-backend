<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->enum('vet_status', ['pending', 'approved', 'rejected', 'suspended'])
                ->default('pending')
                ->after('is_verified');
        });

        // Migrate existing data: is_verified=true → approved, false → pending
        DB::table('vet_profiles')
            ->where('is_verified', true)
            ->update(['vet_status' => 'approved']);

        DB::table('vet_profiles')
            ->where('is_verified', false)
            ->whereNotNull('rejection_reason')
            ->update(['vet_status' => 'rejected']);
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn('vet_status');
        });
    }
};
