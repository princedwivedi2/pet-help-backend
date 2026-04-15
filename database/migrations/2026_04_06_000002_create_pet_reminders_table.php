<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_reminders')) {
            return;
        }

        Schema::create('pet_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->enum('reminder_type', [
                'vaccination', 'medication', 'checkup', 'grooming', 
                'feeding', 'exercise', 'training', 'deworming', 
                'flea_treatment', 'dental_care', 'weight_check', 'other'
            ]);
            
            // Scheduling
            $table->timestamp('scheduled_at');
            $table->integer('frequency')->nullable()->comment('How often to repeat');
            $table->enum('frequency_unit', ['minutes', 'hours', 'days', 'weeks', 'months', 'years'])->nullable();
            $table->timestamp('end_date')->nullable();
            
            // Completion tracking
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            
            // Notification settings
            $table->json('notification_methods')->nullable()->comment('database,email,sms,push');
            $table->integer('advance_notice_minutes')->default(60);
            
            // Priority and details
            $table->tinyInteger('priority')->default(5)->comment('1-10 scale');
            $table->string('location')->nullable();
            $table->decimal('cost_estimate', 10, 2)->nullable();
            $table->text('notes')->nullable();
            
            // Related medication (FK intentionally added later because pet_medications is created in a later migration)
            $table->unsignedBigInteger('related_medication_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pet_id', 'scheduled_at']);
            $table->index(['user_id', 'scheduled_at']);
            $table->index(['reminder_type', 'scheduled_at']);
            $table->index(['is_completed', 'scheduled_at']);
            $table->index(['priority', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_reminders');
    }
};