<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_banners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title', 200);
            $table->string('image_url', 500);
            $table->string('link_url', 500)->nullable();
            $table->enum('position', ['home_top', 'home_bottom', 'sidebar', 'vet_listing'])->default('home_top');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'position']);
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('type', ['user', 'vet']); // who can buy
            $table->unsignedInteger('price'); // in paise
            $table->unsignedSmallInteger('duration_days');
            $table->json('features')->nullable(); // list of features
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('ad_banners');
    }
};
