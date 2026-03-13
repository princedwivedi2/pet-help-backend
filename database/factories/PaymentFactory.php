<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vet_profile_id' => VetProfile::factory(),
            'payable_type' => 'appointment',
            'payable_id' => 1,
            'razorpay_order_id' => 'order_mock_' . uniqid(),
            'amount' => 500,
            'platform_fee' => 500,
            'commission_amount' => 0,
            'vet_payout_amount' => 0,
            'payment_model' => 'platform_fee',
            'payment_mode' => 'online',
            'payment_status' => 'created',
            'currency' => 'INR',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'razorpay_payment_id' => 'pay_mock_' . uniqid(),
            'paid_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'payment_mode' => 'offline',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function forSos(): static
    {
        return $this->state(fn () => [
            'payable_type' => 'sos_request',
        ]);
    }

    public function fullPayment(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 500;
            $commission = (int) round($amount * 0.15);
            return [
                'payment_model' => 'full_payment',
                'platform_fee' => $commission,
                'commission_amount' => $commission,
                'vet_payout_amount' => $amount - $commission,
            ];
        });
    }
}
