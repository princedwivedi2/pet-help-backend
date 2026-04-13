<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('title')->nullable();
            $table->text('content');
            $table->enum('note_type', [
                'daily', 'health', 'behavior', 'feeding', 'exercise', 
                'training', 'grooming', 'vet_visit', 'medication', 'other'
            ])->default('daily');
            
            // Mood and activity tracking
            $table->tinyInteger('mood_rating')->nullable()->comment('1-10 scale');
            $table->tinyInteger('activity_level')->nullable()->comment('1-10 scale');
            
            // Media and organization
            $table->json('photo_urls')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('reminder_at')->nullable();
            
            // Privacy and favorites
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_private')->default(false);
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pet_id', 'user_id']);
            $table->index(['note_type', 'created_at']);
            $table->index(['is_favorite', 'created_at']);
            $table->index(['reminder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_notes');
    }
};