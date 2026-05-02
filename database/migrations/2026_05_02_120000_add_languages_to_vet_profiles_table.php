<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vet_profiles') || Schema::hasColumn('vet_profiles', 'languages')) {
            return;
        }

        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->json('languages')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vet_profiles') || !Schema::hasColumn('vet_profiles', 'languages')) {
            return;
        }

        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn('languages');
        });
    }
};