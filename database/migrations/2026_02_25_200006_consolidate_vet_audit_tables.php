<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 4 — Consolidate parallel audit tables.
 *
 * Problem:
 * Two tables record the same events simultaneously:
 *   - vet_verification_logs (original, minimal: reason + freeform metadata JSON)
 *   - vet_verifications     (new, rich: notes + verified_fields + document_snapshot + missing_fields)
 *
 * Strategy:
 * 1. Expand vet_verifications to include ALL data from vet_verification_logs
 * 2. Migrate existing vet_verification_logs rows → vet_verifications
 * 3. Keep vet_verification_logs table but mark as deprecated (do not drop yet)
 *    Dropping in same release is risky if any code path still reads from it.
 *
 * The canonical audit source of truth is: vet_verifications
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add 'applied' and 'approval_blocked' to vet_verifications action enum
        DB::statement("ALTER TABLE vet_verifications MODIFY COLUMN action ENUM('applied','approved','rejected','suspended','reactivated','approval_blocked') NOT NULL");

        // Step 2: Make admin_id nullable (for 'applied' action — self-submitted)
        Schema::table('vet_verifications', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->change();
        });

        // Step 3: Add reason column (maps from vet_verification_logs.reason)
        Schema::table('vet_verifications', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('notes');
        });

        // Step 4: Migrate existing vet_verification_logs data → vet_verifications
        // Only rows that don't already have a matching record
        DB::statement("
            INSERT INTO vet_verifications (uuid, vet_profile_id, admin_id, action, reason, notes, verified_fields, created_at, updated_at)
            SELECT
                vvl.uuid,
                vvl.vet_profile_id,
                vvl.admin_id,
                vvl.action,
                vvl.reason,
                NULL,
                vvl.metadata,
                vvl.created_at,
                vvl.updated_at
            FROM vet_verification_logs vvl
            WHERE NOT EXISTS (
                SELECT 1 FROM vet_verifications vv
                WHERE vv.vet_profile_id = vvl.vet_profile_id
                  AND vv.action = vvl.action
                  AND vv.created_at = vvl.created_at
            )
        ");

        // Step 5: Drop the FK on vet_verification_logs → vet_profiles
        // (it was just created in migration 200005, but we're deprecating this table)
        if ($this->foreignKeyExists('vet_verification_logs', 'vet_verification_logs_vet_profile_id_foreign')) {
            Schema::table('vet_verification_logs', function (Blueprint $table) {
                $table->dropForeign(['vet_profile_id']);
            });
        }
        if ($this->foreignKeyExists('vet_verification_logs', 'vet_verification_logs_admin_id_foreign')) {
            Schema::table('vet_verification_logs', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
            });
        }

        // Step 6: Rename deprecated table (keeps data accessible but clearly marked)
        Schema::rename('vet_verification_logs', '_deprecated_vet_verification_logs');
    }

    public function down(): void
    {
        // Restore the old table name
        Schema::rename('_deprecated_vet_verification_logs', 'vet_verification_logs');

        // Re-add FKs
        Schema::table('vet_verification_logs', function (Blueprint $table) {
            $table->foreign('vet_profile_id')->references('id')->on('vet_profiles')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });

        // Remove reason column
        Schema::table('vet_verifications', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        // Revert enum
        DB::statement("ALTER TABLE vet_verifications MODIFY COLUMN action ENUM('approved','rejected','suspended','reactivated') NOT NULL");
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraintName]);

        return $result[0]->cnt > 0;
    }
};
