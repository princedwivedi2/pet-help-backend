<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sos_request_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');

            // Visit details
            $table->text('visit_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('prescription_file_url', 500)->nullable();
            $table->json('before_images')->nullable(); // array of URLs
            $table->json('after_images')->nullable();
            $table->json('treatment_cost_breakdown')->nullable(); // [{item, cost}]
            $table->unsignedInteger('total_treatment_cost')->nullable(); // in paise

            // Follow-up
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('appointment_id');
            $table->index('sos_request_id');
            $table->index('vet_profile_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_records');
    }
};
