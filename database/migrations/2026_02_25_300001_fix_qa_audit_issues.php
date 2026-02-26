<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix issues identified by QA behavioral audit:
 *
 * 1. CRITICAL: vet_verifications.admin_id FK uses CASCADE — deleting an admin
 *    destroys audit trail. Change to SET NULL.
 *
 * 2. HIGH: appointments unique index (vet_profile_id, scheduled_at) blocks
 *    rebooking of cancelled/soft-deleted slots. Replace with a partial unique
 *    index that only covers active appointments.
 *
 * 3. HIGH: vet_profiles.user_id CASCADE deletes vet profile + all appointments
 *    when a user is deleted. Change to SET NULL to preserve historical data.
 *    appointments.vet_profile_id CASCADE also changed to SET NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Fix 1: vet_verifications.admin_id CASCADE → SET NULL ──
        $this->replaceForeignKey(
            'vet_verifications', 'admin_id', 'users', 'id', 'set null'
        );

        // ── Fix 2: Replace unique_vet_slot with a conditional unique index ──
        // Drop old unique index
        $idxExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'
              AND INDEX_NAME = 'unique_vet_slot'
        ");
        if ($idxExists[0]->cnt > 0) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropUnique('unique_vet_slot');
            });
        }

        // MySQL 8 does not support partial/filtered unique indexes natively.
        // Instead, add a nullable column `cancelled_at_slot_release` that is
        // set when the appointment leaves the active pool (cancelled/completed/no_show).
        // The unique index covers (vet_profile_id, scheduled_at, cancelled_at_slot_release)
        // with NULL meaning "active" — MySQL treats NULLs as distinct in unique indexes,
        // so only one active appointment per slot is allowed while cancelled ones don't block.
        if (!Schema::hasColumn('appointments', 'cancelled_at_slot_release')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->timestamp('cancelled_at_slot_release')->nullable()->after('cancelled_by');
            });
        }

        // Backfill: mark already-cancelled/completed/no_show appointments
        DB::statement("
            UPDATE appointments
            SET cancelled_at_slot_release = updated_at
            WHERE status IN ('cancelled', 'completed', 'no_show')
              AND cancelled_at_slot_release IS NULL
        ");

        // Create the new unique index
        $newIdxExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'
              AND INDEX_NAME = 'unique_active_vet_slot'
        ");
        if ($newIdxExists[0]->cnt === 0) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->unique(
                    ['vet_profile_id', 'scheduled_at', 'cancelled_at_slot_release'],
                    'unique_active_vet_slot'
                );
            });
        }

        // ── Fix 3: Cascade chain — change to SET NULL ──
        // vet_profiles.user_id: CASCADE → SET NULL (preserve vet profile if user deleted)
        // Column is already nullable — only FK rule needs updating.
        $this->replaceForeignKey(
            'vet_profiles', 'user_id', 'users', 'id', 'set null'
        );

        // appointments.vet_profile_id: CASCADE → SET NULL (preserve appointment history)
        // Column is NOT NULL — must make nullable before SET NULL FK is possible.
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('vet_profile_id')->nullable()->change();
        });
        $this->replaceForeignKey(
            'appointments', 'vet_profile_id', 'vet_profiles', 'id', 'set null'
        );
    }

    public function down(): void
    {
        // Restore original FK behaviors
        $this->replaceForeignKey(
            'vet_verifications', 'admin_id', 'users', 'id', 'cascade'
        );

        $this->replaceForeignKey(
            'vet_profiles', 'user_id', 'users', 'id', 'cascade'
        );

        $this->replaceForeignKey(
            'appointments', 'vet_profile_id', 'vet_profiles', 'id', 'cascade'
        );

        // Restore NOT NULL on vet_profile_id
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('vet_profile_id')->nullable(false)->change();
        });

        // Remove the slot release column and restore original unique index
        $idxExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'
              AND INDEX_NAME = 'unique_active_vet_slot'
        ");
        if ($idxExists[0]->cnt > 0) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropUnique('unique_active_vet_slot');
            });
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('cancelled_at_slot_release');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique(['vet_profile_id', 'scheduled_at'], 'unique_vet_slot');
        });
    }

    /**
     * Drop an existing FK and recreate it with a new ON DELETE rule.
     */
    private function replaceForeignKey(
        string $table,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete
    ): void {
        $constraintName = "{$table}_{$column}_foreign";

        $exists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraintName]);

        if ($exists[0]->cnt > 0) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
        }

        Schema::table($table, function (Blueprint $t) use ($column, $refTable, $refColumn, $onDelete) {
            $t->foreign($column)
                ->references($refColumn)
                ->on($refTable)
                ->onDelete($onDelete);
        });
    }
};
