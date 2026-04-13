<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('flag_reason');
            $table->boolean('is_verified_purchase')->default(false)->after('is_visible');
        });

        // Update existing reviews to set verified purchase based on linked appointment/SOS
        DB::statement("
            UPDATE reviews 
            SET is_verified_purchase = 1 
            WHERE appointment_id IN (SELECT id FROM appointments WHERE status = 'completed')
            OR sos_request_id IN (SELECT id FROM sos_requests WHERE status = 'resolved')
        ");
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'is_verified_purchase']);
        });
    }
};
