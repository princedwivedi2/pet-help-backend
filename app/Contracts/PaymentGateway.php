<?php

namespace App\Contracts;

/**
 * Payment gateway interface.
 *
 * Stub for future implementation. Implement this interface
 * with Stripe, Razorpay, or other payment providers.
 */
interface PaymentGateway
{
    /**
     * Create a payment intent / order.
     *
     * @param int    $amountInPaise  Amount in smallest currency unit
     * @param string $currency       Currency code (INR, USD, etc.)
     * @param array  $metadata       Additional metadata
     * @return array ['order_id' => string, 'client_secret' => string, ...]
     */
    public function createPaymentIntent(int $amountInPaise, string $currency = 'INR', array $metadata = []): array;

    /**
     * Verify a payment by ID.
     *
     * @param string $paymentId
     * @return array ['verified' => bool, 'status' => string, ...]
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Process a refund.
     *
     * @param string   $paymentId
     * @param int|null $amount  Partial refund amount, null = full
     * @return array
     */
    public function refund(string $paymentId, ?int $amount = null): array;
}
 