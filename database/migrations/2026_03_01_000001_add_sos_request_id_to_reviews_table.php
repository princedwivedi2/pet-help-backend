<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('sos_request_id')->nullable()->after('appointment_id')
                ->constrained('sos_requests')->onDelete('set null');

            $table->unique(['user_id', 'sos_request_id'], 'unique_user_sos_review');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['sos_request_id']);
            $table->dropUnique('unique_user_sos_review');
            $table->dropColumn('sos_request_id');
        });
    }
};
