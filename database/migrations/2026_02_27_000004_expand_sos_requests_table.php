<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            // Vet tracking
            $table->decimal('vet_latitude', 10, 8)->nullable()->after('assigned_vet_id');
            $table->decimal('vet_longitude', 11, 8)->nullable()->after('vet_latitude');

            // Timing
            $table->unsignedInteger('response_time_seconds')->nullable()->after('resolution_notes');
            $table->unsignedInteger('arrival_time_seconds')->nullable()->after('response_time_seconds');
            $table->timestamp('vet_departed_at')->nullable()->after('arrival_time_seconds');
            $table->timestamp('vet_arrived_at')->nullable()->after('vet_departed_at');
            $table->timestamp('treatment_started_at')->nullable()->after('vet_arrived_at');

            // Charges
            $table->unsignedInteger('emergency_charge')->nullable()->after('treatment_started_at');
            $table->decimal('distance_travelled_km', 8, 2)->nullable()->after('emergency_charge');

            // Auto-expire
            $table->timestamp('auto_expire_at')->nullable()->after('distance_travelled_km');

            $table->index('auto_expire_at');
        });

        // Expand SOS status enum
        DB::statement("ALTER TABLE sos_requests MODIFY COLUMN status ENUM(
            'pending','acknowledged','in_progress','completed','cancelled',
            'sos_pending','sos_accepted','vet_on_the_way','arrived',
            'treatment_in_progress','sos_completed','sos_cancelled','expired'
        ) DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropIndex(['auto_expire_at']);
            $table->dropColumn([
                'vet_latitude', 'vet_longitude',
                'response_time_seconds', 'arrival_time_seconds',
                'vet_departed_at', 'vet_arrived_at', 'treatment_started_at',
                'emergency_charge', 'distance_travelled_km', 'auto_expire_at',
            ]);
        });

        DB::statement("ALTER TABLE sos_requests MODIFY COLUMN status ENUM(
            'pending','acknowledged','in_progress','completed','cancelled'
        ) DEFAULT 'pending'");
    }
};
