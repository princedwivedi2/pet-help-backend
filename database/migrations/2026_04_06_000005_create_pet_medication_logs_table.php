<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_medication_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_medication_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Administration details
            $table->timestamp('administered_at');
            $table->string('administered_by')->nullable()->comment('Who gave the medication');
            $table->string('dosage_given');
            $table->string('dosage_unit', 50);
            $table->string('administration_method');
            
            // Success and reaction tracking
            $table->boolean('was_successful')->default(true);
            $table->enum('pet_reaction', [
                'normal', 'positive', 'mild_discomfort', 
                'refused', 'vomited', 'allergic_reaction', 'other'
            ])->default('normal');
            
            $table->text('side_effects_observed')->nullable();
            $table->text('notes')->nullable();
            $table->json('photo_urls')->nullable();
            $table->string('location')->nullable();
            
            // Next dose tracking
            $table->timestamp('next_dose_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['pet_medication_id', 'administered_at']);
            $table->index(['user_id', 'administered_at']);
            $table->index(['was_successful', 'administered_at']);
            $table->index(['pet_reaction', 'administered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_medication_logs');
    }
};