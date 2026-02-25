<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['approved', 'rejected', 'suspended', 'reactivated']);
            $table->text('notes')->nullable();
            $table->json('verified_fields')->nullable();
            $table->json('document_snapshot')->nullable();
            $table->json('missing_fields')->nullable();
            $table->timestamps();

            $table->index(['vet_profile_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_verifications');
    }
};
