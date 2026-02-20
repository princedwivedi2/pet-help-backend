<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address', 300)->nullable();
            $table->text('description');
            $table->enum('emergency_type', ['injury', 'illness', 'poisoning', 'accident', 'breathing', 'seizure', 'other'])->default('other');
            $table->enum('status', ['pending', 'acknowledged', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_vet_id')->nullable()->constrained('vet_profiles')->onDelete('set null');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_requests');
    }
};
