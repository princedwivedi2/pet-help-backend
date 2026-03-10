<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');

            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->text('vet_reply')->nullable();
            $table->timestamp('vet_replied_at')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'appointment_id'], 'unique_user_appointment_review');
            $table->index('vet_profile_id');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
