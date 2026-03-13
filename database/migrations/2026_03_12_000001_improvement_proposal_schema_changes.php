<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── VetProfile enhancements ──
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_profiles', 'online_fee')) {
                $table->unsignedInteger('online_fee')->nullable()->after('home_visit_fee');
            }
            if (!Schema::hasColumn('vet_profiles', 'max_home_visit_km')) {
                $table->unsignedInteger('max_home_visit_km')->default(10)->after('online_fee');
            }
        });

        // ── SOS Request enhancements ──
        Schema::table('sos_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('sos_requests', 'response_type')) {
                $table->string('response_type', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('sos_requests', 'estimated_arrival_at')) {
                $table->timestamp('estimated_arrival_at')->nullable()->after('response_type');
            }
        });

        // ── Appointment enhancements ──
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'payment_mode')) {
                $table->string('payment_mode', 20)->default('online')->after('payment_status');
            }
        });

        // ── Standardize SOS statuses (migrate old names to new) ──
        DB::table('sos_requests')->where('status', 'pending')->update(['status' => 'sos_pending']);
        DB::table('sos_requests')->where('status', 'acknowledged')->update(['status' => 'sos_accepted']);
        DB::table('sos_requests')->where('status', 'in_progress')->update(['status' => 'sos_in_progress']);
        DB::table('sos_requests')->where('status', 'treatment_in_progress')->update(['status' => 'sos_in_progress']);
        DB::table('sos_requests')->where('status', 'completed')->update(['status' => 'sos_completed']);
        DB::table('sos_requests')->where('status', 'cancelled')->update(['status' => 'sos_cancelled']);
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn(['online_fee', 'max_home_visit_km']);
        });

        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropColumn(['response_type', 'estimated_arrival_at']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('payment_mode');
        });
    }
};
