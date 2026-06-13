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
            // Phase 20 audit (BE-09): 'sos_in_progress' is a deprecated duplicate of 'in_progress'.
            // It was removed from the MySQL MODIFY statement in the 2026_02_27 expand migration.
            // Canonical active-progress value is 'in_progress'. No data migration required.
            // SosController and SosRequest model reference only 'in_progress' for post-acceptance flow.
            $table->enum('status', [
                'pending', 'acknowledged', 'in_progress', 'completed', 'cancelled',
                'sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived',
                'sos_in_progress', 'treatment_in_progress', 'sos_completed', 'sos_cancelled', 'expired',
            ])->default('pending');
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
