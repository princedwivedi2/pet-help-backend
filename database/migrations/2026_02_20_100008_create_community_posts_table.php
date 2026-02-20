<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('topic_id')->constrained('community_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 191);
            $table->longText('content');
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['topic_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
