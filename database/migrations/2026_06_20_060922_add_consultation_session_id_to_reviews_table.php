<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('consultation_session_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained('consultation_sessions')
                ->onDelete('set null');

            $table->unique(['user_id', 'consultation_session_id'], 'unique_user_consultation_review');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['consultation_session_id']);
            $table->dropUnique('unique_user_consultation_review');
            $table->dropColumn('consultation_session_id');
        });
    }
};
