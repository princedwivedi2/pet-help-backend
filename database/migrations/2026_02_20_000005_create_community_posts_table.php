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
            $table->foreignId('community_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 300);
            $table->longText('content');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['community_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
