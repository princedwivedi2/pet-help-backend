<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_medical_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by_vet_id')->nullable()->constrained('vet_profiles')->nullOnDelete();
            $table->foreignId('visit_record_id')->nullable()->constrained('visit_records')->nullOnDelete();

            $table->enum('record_type', [
                'diagnosis',
                'vaccination',
                'medicine',
                'lab_report',
                'general',
            ])->default('general');

            $table->string('title', 255);
            $table->text('description')->nullable();

            // Medicine-specific fields (populated when record_type = 'medicine')
            $table->string('medicine_name', 255)->nullable();
            $table->string('medicine_dosage', 100)->nullable();
            $table->string('medicine_frequency', 100)->nullable();
            $table->string('medicine_duration', 100)->nullable();

            $table->string('attachment_url', 500)->nullable();
            $table->date('recorded_at');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common query patterns
            $table->index(['pet_id', 'record_type']);
            $table->index(['pet_id', 'recorded_at']);
            $table->index(['pet_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_medical_records');
    }
};
