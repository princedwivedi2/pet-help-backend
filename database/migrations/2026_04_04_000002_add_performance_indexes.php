<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for common query patterns to improve performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SOS requests - common query patterns
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'sos_user_status_created');
            $table->index(['status', 'auto_expire_at'], 'sos_status_expire');
            $table->index(['assigned_vet_id', 'status'], 'sos_vet_status');
        });

        // Appointments - common query patterns
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'scheduled_at'], 'apt_user_status_scheduled');
            $table->index(['vet_profile_id', 'status', 'scheduled_at'], 'apt_vet_status_scheduled');
            $table->index(['scheduled_at', 'status'], 'apt_scheduled_status');
        });

        // Reviews - vet lookup with status
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['vet_profile_id', 'is_visible', 'created_at'], 'rev_vet_visible_created');
        });

        // Payments - status queries
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id', 'payment_status', 'created_at'], 'pay_user_status_created');
            $table->index(['vet_profile_id', 'payment_status'], 'pay_vet_status');
        });

        // Notifications - common read status queries
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notif_user_read');
        });

        // Blog posts - published listing
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index(['is_published', 'published_at'], 'blog_published');
        });

        // Community posts - topic listing
        Schema::table('community_posts', function (Blueprint $table) {
            $table->index(['topic_id', 'is_visible', 'created_at'], 'comm_topic_visible_created');
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropIndex('sos_user_status_created');
            $table->dropIndex('sos_status_expire');
            $table->dropIndex('sos_vet_status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('apt_user_status_scheduled');
            $table->dropIndex('apt_vet_status_scheduled');
            $table->dropIndex('apt_scheduled_status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('rev_vet_visible_created');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('pay_user_status_created');
            $table->dropIndex('pay_vet_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notif_user_read');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_published');
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropIndex('comm_topic_visible_created');
        });
    }
};
