<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_replies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('community_replies')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
            $table->index('parent_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_replies');
    }
};
