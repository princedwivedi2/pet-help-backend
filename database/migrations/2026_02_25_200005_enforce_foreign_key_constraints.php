<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 (continued) — Enforce real foreign key constraints.
 *
 * MUST run AFTER 200001 (InnoDB conversion) and 200003 (column removals).
 * MyISAM silently accepted all constrained() declarations without creating FKs.
 * Now with InnoDB we can enforce referential integrity at DB level.
 *
 * Strategy:
 * - Clean orphan data FIRST (set dangling FKs to NULL or delete)
 * - Then add the FK constraints
 * - Only add FKs for tables with actual data relationships
 * - Skip framework tables (cache, jobs, sessions) — no FK needed
 *
 * NOTE: Some FKs were declared in migrations but never actually created.
 * We re-declare them here to ensure they exist regardless of migration history.
 */
return new class extends Migration
{
    public function up(): void
    {
        // — Orphan cleanup (safe: set to NULL or skip if no orphans) —

        // pets.user_id → users.id
        DB::statement("UPDATE pets SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users) AND user_id IS NOT NULL");

        // sos_requests.user_id → users.id
        DB::statement("DELETE FROM sos_requests WHERE user_id NOT IN (SELECT id FROM users)");

        // sos_requests.pet_id → pets.id
        DB::statement("UPDATE sos_requests SET pet_id = NULL WHERE pet_id IS NOT NULL AND pet_id NOT IN (SELECT id FROM pets)");

        // sos_requests.assigned_vet_id → vet_profiles.id
        DB::statement("UPDATE sos_requests SET assigned_vet_id = NULL WHERE assigned_vet_id IS NOT NULL AND assigned_vet_id NOT IN (SELECT id FROM vet_profiles)");

        // incident_logs.user_id → users.id
        DB::statement("DELETE FROM incident_logs WHERE user_id NOT IN (SELECT id FROM users)");

        // vet_profiles.user_id → users.id
        DB::statement("UPDATE vet_profiles SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)");

        // vet_verification_logs.vet_profile_id → vet_profiles.id
        DB::statement("DELETE FROM vet_verification_logs WHERE vet_profile_id NOT IN (SELECT id FROM vet_profiles)");

        // vet_verifications.vet_profile_id → vet_profiles.id
        DB::statement("DELETE FROM vet_verifications WHERE vet_profile_id NOT IN (SELECT id FROM vet_profiles)");

        // vet_verifications.admin_id → users.id
        DB::statement("DELETE FROM vet_verifications WHERE admin_id NOT IN (SELECT id FROM users)");

        // vet_verification_logs.admin_id → users.id (nullable)
        DB::statement("UPDATE vet_verification_logs SET admin_id = NULL WHERE admin_id IS NOT NULL AND admin_id NOT IN (SELECT id FROM users)");

        // — Now add FK constraints —

        // pets
        $this->addForeignKeyIfNotExists('pets', 'user_id', 'users', 'id', 'cascade');

        // vet_profiles
        $this->addForeignKeyIfNotExists('vet_profiles', 'user_id', 'users', 'id', 'cascade');

        // vet_availabilities
        $this->addForeignKeyIfNotExists('vet_availabilities', 'vet_profile_id', 'vet_profiles', 'id', 'cascade');

        // vet_verification_logs
        $this->addForeignKeyIfNotExists('vet_verification_logs', 'vet_profile_id', 'vet_profiles', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('vet_verification_logs', 'admin_id', 'users', 'id', 'set null');

        // vet_verifications
        $this->addForeignKeyIfNotExists('vet_verifications', 'vet_profile_id', 'vet_profiles', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('vet_verifications', 'admin_id', 'users', 'id', 'cascade');

        // sos_requests
        $this->addForeignKeyIfNotExists('sos_requests', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('sos_requests', 'pet_id', 'pets', 'id', 'set null');
        $this->addForeignKeyIfNotExists('sos_requests', 'assigned_vet_id', 'vet_profiles', 'id', 'set null');

        // incident_logs
        $this->addForeignKeyIfNotExists('incident_logs', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('incident_logs', 'pet_id', 'pets', 'id', 'set null');
        $this->addForeignKeyIfNotExists('incident_logs', 'sos_request_id', 'sos_requests', 'id', 'set null');
        $this->addForeignKeyIfNotExists('incident_logs', 'vet_profile_id', 'vet_profiles', 'id', 'set null');

        // appointments
        $this->addForeignKeyIfNotExists('appointments', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('appointments', 'vet_profile_id', 'vet_profiles', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('appointments', 'pet_id', 'pets', 'id', 'set null');
        $this->addForeignKeyIfNotExists('appointments', 'cancelled_by', 'users', 'id', 'set null');

        // emergency_guides
        $this->addForeignKeyIfNotExists('emergency_guides', 'category_id', 'emergency_categories', 'id', 'cascade');

        // blog module
        $this->addForeignKeyIfNotExists('blog_posts', 'category_id', 'blog_categories', 'id', 'set null');
        $this->addForeignKeyIfNotExists('blog_posts', 'author_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_post_tag', 'blog_post_id', 'blog_posts', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_post_tag', 'blog_tag_id', 'blog_tags', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_comments', 'blog_post_id', 'blog_posts', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_comments', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_likes', 'blog_post_id', 'blog_posts', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('blog_likes', 'user_id', 'users', 'id', 'cascade');

        // community module
        $this->addForeignKeyIfNotExists('community_posts', 'topic_id', 'community_topics', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_posts', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_replies', 'post_id', 'community_posts', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_replies', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_replies', 'parent_id', 'community_replies', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_reports', 'user_id', 'users', 'id', 'cascade');
        $this->addForeignKeyIfNotExists('community_reports', 'reviewed_by', 'users', 'id', 'set null');
        $this->addForeignKeyIfNotExists('community_votes', 'user_id', 'users', 'id', 'cascade');

        // personal_access_tokens (polymorphic — skip FK, use index only)
    }

    public function down(): void
    {
        $tables = [
            'pets' => ['user_id'],
            'vet_profiles' => ['user_id'],
            'vet_availabilities' => ['vet_profile_id'],
            'vet_verification_logs' => ['vet_profile_id', 'admin_id'],
            'vet_verifications' => ['vet_profile_id', 'admin_id'],
            'sos_requests' => ['user_id', 'pet_id', 'assigned_vet_id'],
            'incident_logs' => ['user_id', 'pet_id', 'sos_request_id', 'vet_profile_id'],
            'appointments' => ['user_id', 'vet_profile_id', 'pet_id', 'cancelled_by'],
            'emergency_guides' => ['category_id'],
            'blog_posts' => ['category_id', 'author_id'],
            'blog_post_tag' => ['blog_post_id', 'blog_tag_id'],
            'blog_comments' => ['blog_post_id', 'user_id'],
            'blog_likes' => ['blog_post_id', 'user_id'],
            'community_posts' => ['topic_id', 'user_id'],
            'community_replies' => ['post_id', 'user_id', 'parent_id'],
            'community_reports' => ['user_id', 'reviewed_by'],
            'community_votes' => ['user_id'],
        ];

        foreach ($tables as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $col) {
                    $constraintName = "{$table}_{$col}_foreign";
                    if ($this->foreignKeyExists($table, $constraintName)) {
                        $t->dropForeign([$col]);
                    }
                }
            });
        }
    }

    /**
     * Add a foreign key constraint only if it doesn't already exist.
     */
    private function addForeignKeyIfNotExists(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete
    ): void {
        $constraintName = "{$table}_{$column}_foreign";

        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $t->foreign($column)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->onDelete($onDelete);
        });
    }

    /**
     * Check if a foreign key constraint exists.
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraintName]);

        return $result[0]->cnt > 0;
    }
};
