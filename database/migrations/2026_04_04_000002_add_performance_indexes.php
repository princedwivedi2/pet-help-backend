<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for common query patterns to improve performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SOS requests - common query patterns
        $this->addIndexIfMissing('sos_requests', ['user_id', 'status', 'created_at'], 'sos_user_status_created');
        $this->addIndexIfMissing('sos_requests', ['status', 'auto_expire_at'], 'sos_status_expire');
        $this->addIndexIfMissing('sos_requests', ['assigned_vet_id', 'status'], 'sos_vet_status');

        // Appointments - common query patterns
        $this->addIndexIfMissing('appointments', ['user_id', 'status', 'scheduled_at'], 'apt_user_status_scheduled');
        $this->addIndexIfMissing('appointments', ['vet_profile_id', 'status', 'scheduled_at'], 'apt_vet_status_scheduled');
        $this->addIndexIfMissing('appointments', ['scheduled_at', 'status'], 'apt_scheduled_status');

        // Reviews - vet lookup with status
        $this->addIndexIfMissing('reviews', ['vet_profile_id', 'is_visible', 'created_at'], 'rev_vet_visible_created');

        // Payments - status queries
        $this->addIndexIfMissing('payments', ['user_id', 'payment_status', 'created_at'], 'pay_user_status_created');
        $this->addIndexIfMissing('payments', ['vet_profile_id', 'payment_status'], 'pay_vet_status');

        // Notifications - common read status queries
        $this->addIndexIfMissing('notifications', ['notifiable_id', 'notifiable_type', 'read_at'], 'notif_user_read');

        // Blog posts - published listing
        $this->addIndexIfMissing('blog_posts', ['is_published', 'published_at'], 'blog_published');

        // Community posts - topic listing
        $this->addIndexIfMissing('community_posts', ['topic_id', 'is_visible', 'created_at'], 'comm_topic_visible_created');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('sos_requests', 'sos_user_status_created');
        $this->dropIndexIfExists('sos_requests', 'sos_status_expire');
        $this->dropIndexIfExists('sos_requests', 'sos_vet_status');

        $this->dropIndexIfExists('appointments', 'apt_user_status_scheduled');
        $this->dropIndexIfExists('appointments', 'apt_vet_status_scheduled');
        $this->dropIndexIfExists('appointments', 'apt_scheduled_status');

        $this->dropIndexIfExists('reviews', 'rev_vet_visible_created');

        $this->dropIndexIfExists('payments', 'pay_user_status_created');
        $this->dropIndexIfExists('payments', 'pay_vet_status');

        $this->dropIndexIfExists('notifications', 'notif_user_read');

        $this->dropIndexIfExists('blog_posts', 'blog_published');

        $this->dropIndexIfExists('community_posts', 'comm_topic_visible_created');
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$tableName}')");
            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                'SELECT COUNT(*) AS count FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$tableName, $indexName]
            );

            return (int) ($result->count ?? 0) > 0;
        }

        $result = DB::selectOne(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$tableName, $indexName]
        );

        return (int) ($result->count ?? 0) > 0;
    }
};
