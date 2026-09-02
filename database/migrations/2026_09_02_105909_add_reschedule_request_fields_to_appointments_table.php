<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google-Meet-style reschedule requests for appointments.
 *
 * When the pet owner requests a new time, the request is held here pending
 * the vet's decision — scheduled_at does NOT change until the vet accepts.
 * When the vet requests a new time, AppointmentService applies it immediately
 * (the vet already committed to the slot), so these columns stay empty for
 * that path.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reschedule_requested_at')->nullable()->after('reschedule_reason');
            $table->timestamp('reschedule_requested_scheduled_at')->nullable()->after('reschedule_requested_at');
            $table->string('reschedule_requested_reason', 500)->nullable()->after('reschedule_requested_scheduled_at');
            $table->string('reschedule_requested_by', 10)->nullable()->after('reschedule_requested_reason'); // 'user' | 'vet'
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'reschedule_requested_at',
                'reschedule_requested_scheduled_at',
                'reschedule_requested_reason',
                'reschedule_requested_by',
            ]);
        });
    }
};
