<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 1 — Convert ALL tables from MyISAM to InnoDB.
 *
 * Why this is critical:
 * - MyISAM does NOT support foreign key constraints (all FK declarations silently ignored)
 * - MyISAM does NOT support transactions (DB::transaction() is a no-op)
 * - MyISAM does NOT support row-level locking (lockForUpdate() is meaningless)
 * - MyISAM is NOT ACID-compliant (crash = data corruption risk)
 *
 * This migration MUST run BEFORE any FK-enforcement migration.
 * Safe on live data — ALTER TABLE ... ENGINE=InnoDB is an in-place operation.
 */
return new class extends Migration
{
    /**
     * Tables to convert, ordered: referenced tables first, then dependents.
     * This order prevents FK creation issues in subsequent migrations.
     */
    private array $tables = [
        // Framework / infrastructure (no FKs)
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'notifications',

        // Root entities (referenced by others)
        'users',
        'personal_access_tokens',
        'pets',
        'emergency_categories',
        'emergency_guides',

        // Vet domain
        'vet_profiles',
        'vet_availabilities',
        'vet_verification_logs',
        'vet_verifications',

        // SOS & Incidents
        'sos_requests',
        'incident_logs',

        // Appointments
        'appointments',

        // Blog module
        'blog_categories',
        'blog_posts',
        'blog_tags',
        'blog_post_tag',
        'blog_comments',
        'blog_likes',

        // Community module
        'community_topics',
        'community_posts',
        'community_replies',
        'community_votes',
        'community_reports',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $engine = DB::selectOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);

            if ($engine && strtolower($engine->ENGINE) !== 'innodb') {
                DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
            }
        }
    }

    public function down(): void
    {
        // Intentionally irreversible — reverting to MyISAM would break all FK constraints.
        // To revert: manually run ALTER TABLE ... ENGINE = MyISAM after dropping all FKs.
    }
};
