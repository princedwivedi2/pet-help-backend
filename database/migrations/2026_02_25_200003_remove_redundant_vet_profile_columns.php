<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Resolve triple state encoding on vet_profiles.
 *
 * Problem: Three columns encode the same concept:
 *   - is_verified (boolean) — legacy, now redundant with vet_status
 *   - vet_status  (enum)    — authoritative state machine
 *   - is_active   (boolean) — derived from vet_status for suspend/reactivate
 *
 * Live data contradiction found:
 *   vet_profiles.id=5: is_verified=0, vet_status='approved' (impossible state)
 *
 * Strategy:
 * 1. Sync is_verified FROM vet_status (data safety)
 * 2. Sync is_active FROM vet_status (data safety)
 * 3. Drop is_verified (redundant — derived from vet_status='approved')
 * 4. Keep is_active BUT document it as "vet self-toggle / vacation mode"
 *    only if business needs independent toggle. For now, keep as derived.
 *
 * Also removes event-scoped columns that belong in vet_verifications:
 *   - rejection_reason → vet_verifications.notes WHERE action='rejected'
 *   - verified_at       → vet_verifications.created_at WHERE action='approved'
 *   - verified_by       → vet_verifications.admin_id WHERE action='approved'
 *
 * Also removes phantom aggregate columns with no source table:
 *   - rating        → no reviews table exists
 *   - review_count  → no reviews table exists
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Sync existing data before any column removal (idempotent — skips if columns already dropped)
        if (Schema::hasColumn('vet_profiles', 'is_verified')) {
            DB::statement("UPDATE vet_profiles SET is_verified = 1 WHERE vet_status = 'approved'");
            DB::statement("UPDATE vet_profiles SET is_verified = 0 WHERE vet_status != 'approved'");
        }

        // Ensure is_active matches (suspended = inactive)
        DB::statement("UPDATE vet_profiles SET is_active = 0 WHERE vet_status = 'suspended'");
        DB::statement("UPDATE vet_profiles SET is_active = 1 WHERE vet_status IN ('approved', 'pending')");

        // Step 2: Backfill vet_verifications from vet_profiles event data
        if (Schema::hasColumn('vet_profiles', 'verified_at')) {
            DB::statement("
                INSERT INTO vet_verifications (uuid, vet_profile_id, admin_id, action, notes, created_at, updated_at)
                SELECT
                    UUID(), vp.id, vp.verified_by, 'approved', NULL, vp.verified_at, vp.verified_at
                FROM vet_profiles vp
                WHERE vp.verified_at IS NOT NULL
                  AND vp.verified_by IS NOT NULL
                  AND vp.vet_status = 'approved'
                  AND NOT EXISTS (
                      SELECT 1 FROM vet_verifications vv
                      WHERE vv.vet_profile_id = vp.id AND vv.action = 'approved'
                  )
            ");
        }

        // Backfill rejection data
        if (Schema::hasColumn('vet_profiles', 'rejection_reason')) {
            DB::statement("
                INSERT INTO vet_verifications (uuid, vet_profile_id, admin_id, action, notes, created_at, updated_at)
                SELECT
                    UUID(), vp.id, vp.verified_by, 'rejected', vp.rejection_reason, vp.updated_at, vp.updated_at
                FROM vet_profiles vp
                WHERE vp.vet_status = 'rejected'
                  AND vp.rejection_reason IS NOT NULL
                  AND vp.verified_by IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM vet_verifications vv
                      WHERE vv.vet_profile_id = vp.id AND vv.action = 'rejected'
                  )
            ");
        }

        // Step 3: Drop is_verified (its index too)
        if (Schema::hasColumn('vet_profiles', 'is_verified')) {
            $idxExists = DB::select("
                SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'vet_profiles'
                  AND INDEX_NAME = 'vet_profiles_is_verified_index'
            ");
            if ($idxExists[0]->cnt > 0) {
                Schema::table('vet_profiles', function (Blueprint $table) {
                    $table->dropIndex(['is_verified']);
                });
            }

            Schema::table('vet_profiles', function (Blueprint $table) {
                $table->dropColumn('is_verified');
            });
        }

        // Step 4: Drop event-scoped columns
        if (Schema::hasColumn('vet_profiles', 'verified_by')) {
            // Drop FK via raw SQL — safe if it doesn't exist
            $fkExists = DB::select("
                SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'vet_profiles'
                  AND CONSTRAINT_NAME = 'vet_profiles_verified_by_foreign'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            if ($fkExists[0]->cnt > 0) {
                DB::statement('ALTER TABLE vet_profiles DROP FOREIGN KEY vet_profiles_verified_by_foreign');
            }

            Schema::table('vet_profiles', function (Blueprint $table) {
                $table->dropColumn(['rejection_reason', 'verified_at', 'verified_by']);
            });
        }

        // Step 5: Drop phantom aggregates
        if (Schema::hasColumn('vet_profiles', 'rating')) {
            Schema::table('vet_profiles', function (Blueprint $table) {
                $table->dropColumn(['rating', 'review_count']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            // Restore rating & review_count
            $table->decimal('rating', 2, 1)->nullable()->after('is_active');
            $table->integer('review_count')->default(0)->after('rating');

            // Restore event columns
            $table->text('rejection_reason')->nullable()->after('vet_status');
            $table->timestamp('verified_at')->nullable()->after('rejection_reason');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->onDelete('set null');
        });

        Schema::table('vet_profiles', function (Blueprint $table) {
            // Restore is_verified
            $table->boolean('is_verified')->default(false)->after('is_24_hours');
            $table->index('is_verified');
        });

        // Backfill is_verified from vet_status
        DB::statement("UPDATE vet_profiles SET is_verified = 1 WHERE vet_status = 'approved'");
    }
};
