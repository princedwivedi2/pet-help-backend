<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vet_profile_id')->unique()->constrained()->onDelete('cascade');

            $table->unsignedBigInteger('balance')->default(0); // in paise
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->unsignedBigInteger('total_paid_out')->default(0);
            $table->unsignedBigInteger('pending_payout')->default(0);

            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('type', ['credit', 'debit', 'payout', 'refund_debit', 'payout_request', 'payout_completed']);
            $table->unsignedInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('description', 500)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');

            $table->timestamps();

            $table->index('vet_profile_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('vet_wallets');
    }
};
