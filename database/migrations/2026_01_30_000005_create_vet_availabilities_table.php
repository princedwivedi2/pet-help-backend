<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0=Sunday, 6=Saturday
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_emergency_hours')->default(false);
            $table->timestamps();

            $table->unique(['vet_profile_id', 'day_of_week', 'is_emergency_hours'], 'vet_avail_profile_day_emergency_unique');
            $table->index(['day_of_week', 'open_time', 'close_time'], 'vet_avail_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_availabilities');
    }
};
