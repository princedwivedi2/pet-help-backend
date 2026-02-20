<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sos_request_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vet_profile_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('incident_type', ['emergency', 'routine_visit', 'vaccination', 'surgery', 'medication', 'other'])->default('other');
            $table->enum('status', ['open', 'in_treatment', 'resolved', 'follow_up_required'])->default('open');
            $table->date('incident_date');
            $table->date('follow_up_date')->nullable();
            $table->json('attachments')->nullable();
            $table->text('vet_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['pet_id', 'incident_date']);
            $table->index('incident_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
