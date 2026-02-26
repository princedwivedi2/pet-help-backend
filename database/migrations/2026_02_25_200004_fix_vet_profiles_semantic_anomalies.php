<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 6 — Fix semantic anomalies on vet_profiles.
 *
 * 1. latitude/longitude: NOT NULL with 0.0 sentinel → make nullable.
 *    Coordinates (0.0, 0.0) is a real location (Gulf of Guinea).
 *    Any geo query would erroneously include these vets.
 *
 * 2. city: derived from address via heuristic — drop it.
 *    Can be computed at query time or added as a generated column if needed.
 *
 * 3. payment_status on appointments: unconstrained varchar → proper enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix geo columns: make nullable, convert 0.0 sentinels to NULL
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });

        DB::statement("UPDATE vet_profiles SET latitude = NULL, longitude = NULL WHERE latitude = 0.00000000 AND longitude = 0.00000000");

        // 2. Drop derived 'city' column
        $idxExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'vet_profiles'
              AND INDEX_NAME = 'vet_profiles_city_index'
        ");
        if ($idxExists[0]->cnt > 0) {
            Schema::table('vet_profiles', function (Blueprint $table) {
                $table->dropIndex(['city']);
            });
        }
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn('city');
        });

        // 3. Fix payment_status: varchar → enum
        DB::statement("ALTER TABLE appointments MODIFY COLUMN payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        // Restore payment_status to varchar
        DB::statement("ALTER TABLE appointments MODIFY COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid'");

        // Restore city
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->string('city', 100)->default('')->after('address');
            $table->index('city');
        });

        // Restore lat/lng to NOT NULL
        DB::statement("UPDATE vet_profiles SET latitude = 0.00000000 WHERE latitude IS NULL");
        DB::statement("UPDATE vet_profiles SET longitude = 0.00000000 WHERE longitude IS NULL");

        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });
    }
};
