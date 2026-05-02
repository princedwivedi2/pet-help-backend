<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('otp_challenges')) {
            return;
        }

        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('identifier', 255)->index();
            $table->enum('channel', ['email', 'sms'])->index();
            $table->string('purpose', 50)->index();
            $table->string('code_hash');
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['identifier', 'channel', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_challenges');
    }
};