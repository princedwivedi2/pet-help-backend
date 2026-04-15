<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add blog post slugs for SEO-friendly URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts') && ! Schema::hasColumn('blog_posts', 'slug')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->string('slug', 255)->nullable()->unique();
            });
        }

        // Generate slugs for existing posts
        if (Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'slug')) {
            \App\Models\BlogPost::whereNull('slug')->each(function ($post) {
                $post->slug = \Illuminate\Support\Str::slug($post->title) . '-' . $post->uuid;
                $post->saveQuietly();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'slug')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
