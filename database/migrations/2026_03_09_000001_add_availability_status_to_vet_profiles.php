<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_profiles', 'availability_status')) {
                $table->enum('availability_status', ['available', 'busy', 'offline', 'on_leave'])
                    ->default('offline')
                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn('availability_status');
        });
    }
};
