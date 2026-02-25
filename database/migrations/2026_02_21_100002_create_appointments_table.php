<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending');

            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('reason', 500);
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('payment_status', 20)->default('unpaid'); // unpaid, paid, refunded
            $table->unsignedInteger('fee_amount')->nullable(); // in smallest currency unit

            $table->timestamps();
            $table->softDeletes();

            // Prevent double-booking: unique slot per vet
            $table->unique(['vet_profile_id', 'scheduled_at'], 'unique_vet_slot');

            $table->index(['user_id', 'status']);
            $table->index(['vet_profile_id', 'status']);
            $table->index('scheduled_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
