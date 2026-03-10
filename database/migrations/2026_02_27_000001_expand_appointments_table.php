<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the existing status enum and rebuild with expanded statuses
        // 2. Add new columns for appointment types, emergency, photos, timestamps, visit verification

        Schema::table('appointments', function (Blueprint $table) {
            // Appointment type & emergency
            $table->enum('appointment_type', ['online', 'clinic_visit', 'home_visit'])
                ->default('clinic_visit')->after('status');
            $table->boolean('is_emergency')->default(false)->after('appointment_type');

            // Photo attachment
            $table->string('photo_url', 500)->nullable()->after('notes');

            // Home visit address
            $table->string('home_address', 500)->nullable()->after('photo_url');
            $table->decimal('home_latitude', 10, 8)->nullable()->after('home_address');
            $table->decimal('home_longitude', 11, 8)->nullable()->after('home_latitude');

            // Lifecycle timestamps
            $table->timestamp('accepted_at')->nullable()->after('cancelled_at_slot_release');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->timestamp('completed_at')->nullable()->after('rejected_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->text('rejection_reason')->nullable()->after('cancelled_at');

            // Visit verification
            $table->timestamp('visit_started_at')->nullable()->after('rejection_reason');
            $table->timestamp('visit_ended_at')->nullable()->after('visit_started_at');
            $table->decimal('vet_start_latitude', 10, 8)->nullable()->after('visit_ended_at');
            $table->decimal('vet_start_longitude', 11, 8)->nullable()->after('vet_start_latitude');
            $table->decimal('vet_end_latitude', 10, 8)->nullable()->after('vet_start_longitude');
            $table->decimal('vet_end_longitude', 11, 8)->nullable()->after('vet_end_latitude');

            // Indexes
            $table->index('appointment_type');
            $table->index('is_emergency');
        });

        // Expand the status enum via raw SQL (MySQL)
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM(
            'pending','accepted','rejected','cancelled_by_user','cancelled_by_vet',
            'in_progress','completed','no_show','confirmed','cancelled'
        ) DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['appointment_type']);
            $table->dropIndex(['is_emergency']);

            $table->dropColumn([
                'appointment_type', 'is_emergency', 'photo_url',
                'home_address', 'home_latitude', 'home_longitude',
                'accepted_at', 'rejected_at', 'completed_at', 'cancelled_at',
                'rejection_reason',
                'visit_started_at', 'visit_ended_at',
                'vet_start_latitude', 'vet_start_longitude',
                'vet_end_latitude', 'vet_end_longitude',
            ]);
        });

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM(
            'pending','confirmed','completed','cancelled','no_show'
        ) DEFAULT 'pending'");
    }
};
