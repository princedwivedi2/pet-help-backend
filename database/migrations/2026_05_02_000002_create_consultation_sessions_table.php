<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online consultation domain.
 *
 * Covers BOTH instant consult (vet-matching) and scheduled-online appointments.
 * Provider-agnostic: room_provider + room_id store opaque references to
 * Twilio/Daily/Agora/LiveKit/etc. — pick one, wire it via VideoProviderInterface.
 *
 * Lifecycle: pending → matching → matched → joining → active → completed
 *                                          ↓
 *                                       failed/expired (auto-refund eligible)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Who's involved
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vet_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();

            // Origin: instant (now) or scheduled (linked to an existing appointment)
            $table->enum('origin', ['instant', 'scheduled'])->default('instant');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();

            // Modality (per spec: video / audio / chat-only)
            $table->enum('modality', ['video', 'audio', 'chat'])->default('video');

            // Issue context (free-text from user when starting an instant consult)
            $table->string('issue_category', 80)->nullable();   // e.g. "vomiting"
            $table->text('issue_description')->nullable();

            // Lifecycle
            $table->enum('status', [
                'pending',     // user posted request, waiting for matching to start
                'matching',    // searching for available vet
                'matched',     // vet accepted, neither party joined yet
                'joining',     // at least one party joined
                'active',      // both parties joined, consult underway
                'completed',   // happy-path end
                'cancelled',   // user/vet cancelled before active
                'expired',     // no vet matched in time
                'failed',      // technical failure (refund-eligible)
            ])->default('pending')->index();

            // Timing
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('vet_joined_at')->nullable();
            $table->timestamp('user_joined_at')->nullable();
            $table->timestamp('started_at')->nullable();   // first moment both parties present
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // Auto-refund triggers (per spec)
            $table->timestamp('vet_no_show_check_at')->nullable();  // matched_at + 10 min watchdog
            $table->unsignedTinyInteger('connection_failures')->default(0);
            $table->boolean('auto_refund_triggered')->default(false);
            $table->string('refund_reason', 120)->nullable();

            // Provider room (Twilio room SID, Daily room URL, Agora channel, etc.)
            $table->string('room_provider', 30)->nullable();
            $table->string('room_id', 200)->nullable();
            $table->json('room_metadata')->nullable();   // recording id, region, etc.

            // Money trail
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('fee_amount')->nullable();   // paise

            // Visit notes (on completion — mirrors VisitRecord but for online)
            $table->text('vet_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['vet_profile_id', 'status']);
            $table->index(['origin', 'status']);
        });

        Schema::create('consultation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_session_id')
                ->constrained('consultation_sessions')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_role', ['user', 'vet', 'system']);
            $table->enum('type', ['text', 'image', 'file', 'system_event'])->default('text');
            $table->text('body')->nullable();
            $table->string('attachment_path', 500)->nullable();   // private disk
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['consultation_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_messages');
        Schema::dropIfExists('consultation_sessions');
    }
};
