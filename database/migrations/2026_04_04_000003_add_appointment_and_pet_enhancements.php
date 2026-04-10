<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add appointment rescheduling, waitlist, and medical history features.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Appointment rescheduling support
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('original_scheduled_at')->nullable()->after('scheduled_at');
            $table->unsignedTinyInteger('reschedule_count')->default(0)->after('payment_mode');
            $table->text('reschedule_reason')->nullable()->after('reschedule_count');
            $table->timestamp('reminder_sent_at')->nullable()->after('completed_at');
            $table->string('timezone', 50)->default('Asia/Kolkata')->after('scheduled_at');
        });

        // Waitlist for appointments
        Schema::create('appointment_waitlist', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');
            $table->date('preferred_date');
            $table->time('preferred_time_start')->nullable();
            $table->time('preferred_time_end')->nullable();
            $table->string('consultation_type', 50)->default('clinic');
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['vet_profile_id', 'preferred_date', 'is_notified'], 'waitlist_vet_date');
            $table->index(['user_id', 'created_at'], 'waitlist_user');
        });

        // Pet medical history
        Schema::table('pets', function (Blueprint $table) {
            $table->string('microchip_number', 50)->nullable()->after('weight');
            $table->json('vaccinations')->nullable()->after('microchip_number');
            $table->json('allergies')->nullable()->after('vaccinations');
            $table->json('medications')->nullable()->after('allergies');
            $table->json('medical_conditions')->nullable()->after('medications');
            $table->date('last_checkup_date')->nullable()->after('medical_conditions');
            $table->text('medical_notes')->nullable()->after('last_checkup_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'original_scheduled_at',
                'reschedule_count',
                'reschedule_reason',
                'reminder_sent_at',
                'timezone',
            ]);
        });

        Schema::dropIfExists('appointment_waitlist');

        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn([
                'microchip_number',
                'vaccinations',
                'allergies',
                'medications',
                'medical_conditions',
                'last_checkup_date',
                'medical_notes',
            ]);
        });
    }
};
