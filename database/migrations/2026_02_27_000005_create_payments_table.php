<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Polymorphic: payable_type = 'appointment' or 'sos_request'
            $table->string('payable_type', 50);
            $table->unsignedBigInteger('payable_id');

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vet_profile_id')->nullable()->constrained()->onDelete('set null');

            // Razorpay fields
            $table->string('razorpay_order_id', 100)->nullable()->unique();
            $table->string('razorpay_payment_id', 100)->nullable()->unique();
            $table->string('razorpay_signature', 200)->nullable();

            // Amounts (in paise / smallest currency unit)
            $table->unsignedInteger('amount');
            $table->unsignedInteger('platform_fee')->default(0);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->unsignedInteger('vet_payout_amount')->default(0);

            // Payment model
            $table->enum('payment_model', ['platform_fee', 'full_payment'])->default('platform_fee');
            $table->enum('payment_mode', ['online', 'offline'])->default('online');
            $table->enum('payment_status', [
                'pending', 'created', 'authorized', 'captured', 'paid',
                'failed', 'refunded', 'partially_refunded'
            ])->default('pending');

            $table->string('currency', 10)->default('INR');
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('razorpay_response')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id']);
            $table->index('payment_status');
            $table->index('user_id');
            $table->index('vet_profile_id');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
