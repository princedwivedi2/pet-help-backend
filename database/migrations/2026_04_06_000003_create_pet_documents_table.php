<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Optional relationships
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sos_request_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vet_profile_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->enum('document_type', [
                'vaccination_record', 'medical_report', 'prescription', 
                'lab_result', 'x_ray', 'insurance_policy', 'registration', 
                'microchip_info', 'pedigree', 'health_certificate', 
                'grooming_record', 'photo', 'video', 'other'
            ]);
            
            // File information
            $table->string('file_path')->nullable();
            $table->string('file_url')->nullable();
            $table->string('file_name');
            $table->bigInteger('file_size')->nullable()->comment('Size in bytes');
            $table->string('mime_type', 100)->nullable();
            
            // Document metadata
            $table->timestamp('document_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->json('tags')->nullable();
            
            // Privacy and sharing
            $table->boolean('is_confidential')->default(false);
            $table->json('sharing_permissions')->nullable()->comment('Who can access this document');
            $table->text('qr_code_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pet_id', 'document_type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['document_type', 'created_at']);
            $table->index(['expiry_date']);
            $table->index(['is_confidential']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_documents');
    }
};