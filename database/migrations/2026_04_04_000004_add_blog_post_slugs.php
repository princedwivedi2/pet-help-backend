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
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('title');
        });

        // Generate slugs for existing posts
        \App\Models\BlogPost::whereNull('slug')->each(function ($post) {
            $post->slug = \Illuminate\Support\Str::slug($post->title) . '-' . $post->uuid;
            $post->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
