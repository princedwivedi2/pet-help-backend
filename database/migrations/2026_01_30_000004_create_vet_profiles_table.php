<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('clinic_name', 200);
            $table->string('vet_name', 150);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('address', 300);
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->json('services')->nullable();
            $table->json('accepted_species')->nullable();
            $table->boolean('is_emergency_available')->default(false);
            $table->boolean('is_24_hours')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 2, 1)->nullable();
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['latitude', 'longitude']);
            $table->index(['is_active', 'is_emergency_available']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_profiles');
    }
};
