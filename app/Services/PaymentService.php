<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\VetWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private string $razorpayKeyId;
    private string $razorpayKeySecret;

    public function __construct(private AuditService $auditService)
    {
        $this->razorpayKeyId = config('services.razorpay.key_id', '');
        $this->razorpayKeySecret = config('services.razorpay.key_secret', '');
    }

    /**
     * Create a Razorpay order.
     */
    public function createOrder(
        string $payableType,
        int $payableId,
        int $userId,
        ?int $vetProfileId,
        int $amount,
        string $paymentModel = 'platform_fee',
        string $currency = 'INR'
    ): Payment {
        return DB::transaction(function () use ($payableType, $payableId, $userId, $vetProfileId, $amount, $paymentModel, $currency) {
            // Prevent duplicate payment for same payable
            $existing = Payment::where('payable_type', $payableType)
                ->where('payable_id', $payableId)
                ->whereIn('payment_status', ['pending', 'created', 'authorized', 'captured', 'paid'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \DomainException('A payment already exists for this booking.');
            }

            // Calculate fee split
            $fees = $this->calculateFees($amount, $paymentModel);

            // Create Razorpay order via API
            $razorpayOrder = $this->createRazorpayOrder($amount, $currency);

            $payment = Payment::create([
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'user_id' => $userId,
                'vet_profile_id' => $vetProfileId,
                'razorpay_order_id' => $razorpayOrder['id'] ?? null,
                'amount' => $amount,
                'platform_fee' => $fees['platform_fee'],
                'commission_amount' => $fees['commission'],
                'vet_payout_amount' => $fees['vet_payout'],
                'payment_model' => $paymentModel,
                'payment_mode' => 'online',
                'payment_status' => 'created',
                'currency' => $currency,
                'razorpay_response' => $razorpayOrder,
            ]);

            $this->auditService->log(
                $userId,
                Payment::class,
                $payment->id,
                'created',
                null,
                ['amount' => $amount, 'razorpay_order_id' => $payment->razorpay_order_id],
                'Payment order created'
            );

            return $payment;
        });
    }

    /**
     * Verify and capture Razorpay payment.
     *
     * Two-step verification:
     *   1. HMAC signature check (proves the response came from Razorpay's checkout, not tampered).
     *   2. Gateway-side fetch via GET /v1/payments/{id} — asserts status === 'captured',
     *      amount, currency, and order_id ALL match the local Payment row. A valid
     *      signature alone is not enough: the client could replay a captured payment
     *      from another order, or a partially-captured payment.
     *
     * Mock-mode (no live keys, non-production env) skips the gateway fetch.
     */
    public function verifyPayment(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): Payment {
        $this->assertNotTestKeysInProduction();

        // Read-only checks happen OUTSIDE the transaction so that on-failure status
        // updates ('failed') survive — they would otherwise be rolled back when we
        // throw inside the transaction.
        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->firstOrFail();

        // Idempotency: already-paid returns the existing payment (safe retry).
        if ($payment->isPaid()) {
            return $payment;
        }

        // Step 1: HMAC signature.
        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            $this->razorpayKeySecret
        );
        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            Payment::where('id', $payment->id)->update([
                'payment_status' => 'failed',
                'failure_reason' => 'Signature verification failed',
            ]);
            throw new \DomainException('Payment verification failed.');
        }

        // Step 2: Gateway-side reconciliation. Skip in mock mode.
        $gatewayResponse = null;
        if (!$this->isMockMode()) {
            $mismatches = $this->fetchAndCompareWithGateway($payment, $razorpayPaymentId, $razorpayOrderId, $gatewayResponse);
            if (!empty($mismatches)) {
                Payment::where('id', $payment->id)->update([
                    'payment_status' => 'failed',
                    'failure_reason' => 'Gateway reconciliation mismatch: ' . implode(',', array_keys($mismatches)),
                    'razorpay_response' => $gatewayResponse,
                ]);
                Log::critical('Razorpay payment verification mismatch — refusing to mark paid', [
                    'payment_uuid' => $payment->uuid,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'mismatches' => $mismatches,
                ]);
                throw new \DomainException('Payment verification failed.');
            }
        }

        // Step 3: Side-effects atomic — re-read with lock, re-check idempotency, mutate.
        return DB::transaction(function () use ($payment, $razorpayPaymentId, $razorpaySignature) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($locked->isPaid()) {
                return $locked;
            }

            $locked->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($locked->vet_profile_id && $locked->vet_payout_amount > 0) {
                $this->creditVetWallet($locked);
            }
            $this->updatePayableStatus($locked);

            $this->auditService->log(
                $locked->user_id,
                Payment::class,
                $locked->id,
                'payment_captured',
                null,
                ['razorpay_payment_id' => $razorpayPaymentId, 'amount' => $locked->amount],
                'Payment captured successfully'
            );

            Log::info('Payment captured', [
                'payment_uuid' => $locked->uuid,
                'razorpay_payment_id' => $razorpayPaymentId,
                'amount' => $locked->amount,
            ]);

            return $locked;
        });
    }

    /**
     * Fetch the payment from Razorpay and compare every load-bearing field against
     * the local Payment row. Returns a map of `field => [expected, got]` for any
     * mismatch (empty array = all good). Pure read operation — no DB writes.
     *
     * Asserted fields:
     *   - status === 'captured'   (not 'authorized', 'created', 'failed')
     *   - amount === local amount (paise, integer)
     *   - currency === local currency
     *   - order_id === local razorpay_order_id (catches replays from other orders)
     *
     * @throws \RuntimeException on gateway error (5xx, network failure) — caller never sees the row.
     */
    private function fetchAndCompareWithGateway(
        Payment $payment,
        string $razorpayPaymentId,
        string $razorpayOrderId,
        ?array &$gatewayResponse
    ): array {
        $response = Http::withBasicAuth($this->razorpayKeyId, $this->razorpayKeySecret)
            ->timeout(10)
            ->get("https://api.razorpay.com/v1/payments/{$razorpayPaymentId}");

        if ($response->failed()) {
            Log::error('Razorpay payment fetch failed during verify', [
                'payment_uuid' => $payment->uuid,
                'razorpay_payment_id' => $razorpayPaymentId,
                'http_status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Could not verify payment with gateway. Please try again.');
        }

        $gateway = $response->json();
        $gatewayResponse = $gateway;
        $mismatches = [];

        if (($gateway['status'] ?? null) !== 'captured') {
            $mismatches['status'] = ['expected' => 'captured', 'got' => $gateway['status'] ?? null];
        }
        if ((int) ($gateway['amount'] ?? 0) !== (int) $payment->amount) {
            $mismatches['amount'] = ['expected' => (int) $payment->amount, 'got' => (int) ($gateway['amount'] ?? 0)];
        }

        $expectedCurrency = strtoupper((string) ($payment->currency ?: 'INR'));
        $gatewayCurrency = strtoupper((string) ($gateway['currency'] ?? ''));
        if ($gatewayCurrency !== $expectedCurrency) {
            $mismatches['currency'] = ['expected' => $expectedCurrency, 'got' => $gatewayCurrency];
        }

        if (($gateway['order_id'] ?? null) !== $razorpayOrderId) {
            $mismatches['order_id'] = ['expected' => $razorpayOrderId, 'got' => $gateway['order_id'] ?? null];
        }

        return $mismatches;
    }

    /**
     * Process refund.
     */
    public function refund(Payment $payment, ?int $amount = null, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$payment->isPaid()) {
                throw new \DomainException('Only paid payments can be refunded.');
            }

            $refundAmount = $amount ?? $payment->amount;

            // Call Razorpay refund API FIRST — only update status after confirmation (MED-08)
            if ($payment->razorpay_payment_id) {
                try {
                    $this->createRazorpayRefund($payment->razorpay_payment_id, $refundAmount);
                } catch (\Throwable $e) {
                    // Razorpay refund failed — do NOT update payment status
                    Log::error('Razorpay refund failed, payment status unchanged', [
                        'payment_uuid' => $payment->uuid,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            $status = $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded';

            $payment->update([
                'payment_status' => $status,
                'refunded_at' => now(),
                'failure_reason' => $reason ?? 'Refund processed',
            ]);

            // Debit vet wallet if already credited
            if ($payment->vet_profile_id && $payment->vet_payout_amount > 0) {
                $this->debitVetWallet($payment, $refundAmount);
            }

            return $payment;
        });
    }

    /**
     * Record offline payment.
     */
    public function recordOfflinePayment(
        string $payableType,
        int $payableId,
        int $userId,
        ?int $vetProfileId,
        int $amount,
        string $paymentModel = 'platform_fee'
    ): Payment {
        return DB::transaction(function () use ($payableType, $payableId, $userId, $vetProfileId, $amount, $paymentModel) {
            // Prevent duplicate offline payments for same payable (HIGH-02)
            $existing = Payment::where('payable_type', $payableType)
                ->where('payable_id', $payableId)
                ->whereIn('payment_status', ['pending', 'created', 'authorized', 'captured', 'paid'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \DomainException('A payment already exists for this booking.');
            }

            $fees = $this->calculateFees($amount, $paymentModel);

            return Payment::create([
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'user_id' => $userId,
                'vet_profile_id' => $vetProfileId,
                'amount' => $amount,
                'platform_fee' => $fees['platform_fee'],
                'commission_amount' => $fees['commission'],
                'vet_payout_amount' => $fees['vet_payout'],
                'payment_model' => $paymentModel,
                'payment_mode' => 'offline',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }

    // ─── Wallet ─────────────────────────────────────────────────────

    private function creditVetWallet(Payment $payment): void
    {
        $wallet = VetWallet::firstOrCreate(
            ['vet_profile_id' => $payment->vet_profile_id],
            ['balance' => 0, 'total_earned' => 0, 'total_paid_out' => 0, 'pending_payout' => 0]
        );

        $wallet->increment('balance', $payment->vet_payout_amount);
        $wallet->increment('total_earned', $payment->vet_payout_amount);
        $wallet->increment('pending_payout', $payment->vet_payout_amount);

        WalletTransaction::create([
            'vet_profile_id' => $payment->vet_profile_id,
            'payment_id' => $payment->id,
            'type' => 'credit',
            'amount' => $payment->vet_payout_amount,
            'balance_after' => $wallet->fresh()->balance,
            'description' => "Payment #{$payment->uuid} credited",
        ]);
    }

    private function debitVetWallet(Payment $payment, int $refundAmount): void
    {
        // CRIT-01 FIX: Lock wallet row to prevent race condition on concurrent refunds
        $wallet = VetWallet::where('vet_profile_id', $payment->vet_profile_id)
            ->lockForUpdate()
            ->first();
        if (!$wallet) {
            return;
        }

        $startingBalance = $wallet->balance;
        $startingPending = $wallet->pending_payout;
        $debitAmount = min($refundAmount, $startingBalance);

        // CRIT-01: Warn and audit when refund exceeds wallet balance
        if ($refundAmount > $wallet->balance) {
            $deficit = $refundAmount - $startingBalance;
            Log::warning('Vet wallet balance insufficient for full refund debit — platform absorbs deficit', [
                'payment_uuid' => $payment->uuid,
                'vet_profile_id' => $payment->vet_profile_id,
                'refund_amount' => $refundAmount,
                'wallet_balance' => $startingBalance,
                'deficit' => $deficit,
            ]);

            $this->auditService->log(
                null,
                Payment::class,
                $payment->id,
                'refund_deficit',
                null,
                ['deficit' => $deficit, 'wallet_balance' => $wallet->balance, 'refund_amount' => $refundAmount],
                "Wallet deficit: platform absorbed ₹{$deficit}"
            );
        }

        $wallet->decrement('balance', $debitAmount);
        // MED-09 FIX: Wallet already locked, use current value to avoid race
        $pendingDebit = min($debitAmount, $startingPending);
        $wallet->decrement('pending_payout', $pendingDebit);

        $newBalance = max(0, $startingBalance - $debitAmount);

        WalletTransaction::create([
            'vet_profile_id' => $payment->vet_profile_id,
            'payment_id' => $payment->id,
            'type' => 'refund_debit',
            'amount' => $debitAmount,
            'balance_after' => $newBalance,
            'description' => "Refund for payment #{$payment->uuid}",
        ]);
    }

    private function updatePayableStatus(Payment $payment): void
    {
        if ($payment->payable_type === 'appointment' || $payment->payable_type === \App\Models\Appointment::class) {
            // MED-03 FIX: Also set payment_mode on the appointment
            \App\Models\Appointment::where('id', $payment->payable_id)
                ->update([
                    'payment_status' => 'paid',
                    'payment_mode' => $payment->payment_mode,
                ]);
        } elseif ($payment->payable_type === 'sos_request' || $payment->payable_type === \App\Models\SosRequest::class) {
            \App\Models\SosRequest::where('id', $payment->payable_id)
                ->update(['emergency_charge' => $payment->amount]);
        }
    }

    // ─── Fee Calculation ─────────────────────────────────────────────

    private function calculateFees(int $amount, string $paymentModel): array
    {
        if ($paymentModel === 'platform_fee') {
            // Platform keeps the booking fee, vet receives nothing via platform
            return [
                'platform_fee' => $amount,
                'commission' => 0,
                'vet_payout' => 0,
            ];
        }

        // Full payment model: platform takes commission %, rest goes to vet
        $commissionRate = (float) config('services.razorpay.commission_rate', 15); // 15%
        $commission = (int) round($amount * ($commissionRate / 100));
        $vetPayout = $amount - $commission;

        return [
            'platform_fee' => $commission,
            'commission' => $commission,
            'vet_payout' => $vetPayout,
        ];
    }

    // ─── Razorpay API ───────────────────────────────────────────────

    /**
     * Check if payment gateway is properly configured.
     *
     * In production, also rejects test-mode keys (rzp_test_*) so a misconfigured
     * deploy can never accept real money against a test account.
     */
    public function isConfigured(): bool
    {
        if (empty($this->razorpayKeyId) || empty($this->razorpayKeySecret)) {
            return false;
        }

        if (config('app.env') === 'production' && str_starts_with($this->razorpayKeyId, 'rzp_test')) {
            return false;
        }

        return true;
    }

    /**
     * Production safety: throw if test-mode keys are configured in production.
     * Called at the entry of every gateway-touching method.
     */
    private function assertNotTestKeysInProduction(): void
    {
        if (config('app.env') === 'production' && str_starts_with($this->razorpayKeyId, 'rzp_test')) {
            Log::critical('Razorpay test keys detected in production — refusing to process payment', [
                'key_prefix' => substr($this->razorpayKeyId, 0, 8),
            ]);
            throw new \RuntimeException('Payment gateway misconfigured. Please contact support.');
        }
    }

    /**
     * Check if running in mock mode (no real payment processing).
     */
    public function isMockMode(): bool
    {
        return empty($this->razorpayKeyId)
            || empty($this->razorpayKeySecret)
            || (config('app.env') !== 'production' && str_starts_with($this->razorpayKeyId, 'rzp_test'));
    }

    private function createRazorpayOrder(int $amount, string $currency): array
    {
        $this->assertNotTestKeysInProduction();

        if (!$this->isConfigured()) {
            // SECURITY: Block mock orders in production environment
            if (config('app.env') === 'production') {
                Log::critical('Payment gateway not configured in production!', [
                    'amount' => $amount,
                    'currency' => $currency,
                ]);
                throw new \RuntimeException(
                    'Payment gateway is not configured. Please contact support.'
                );
            }

            // Dev/test mode — return mock order with clear warning
            Log::warning('Using mock payment order - NOT FOR PRODUCTION', [
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'id' => 'order_mock_' . uniqid(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'created',
                '_mock' => true, // Flag for frontend/testing
            ];
        }

        $response = Http::withBasicAuth($this->razorpayKeyId, $this->razorpayKeySecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount,
                'currency' => $currency,
                'receipt' => 'receipt_' . uniqid(),
            ]);

        if ($response->failed()) {
            Log::error('Razorpay order creation failed', ['response' => $response->json()]);
            throw new \RuntimeException('Payment gateway error. Please try again.');
        }

        return $response->json();
    }

    private function createRazorpayRefund(string $paymentId, int $amount): array
    {
        if (!$this->isConfigured()) {
            // SECURITY: Block mock refunds in production
            if (config('app.env') === 'production') {
                Log::critical('Payment gateway not configured for refund in production!');
                throw new \RuntimeException(
                    'Payment gateway is not configured. Please contact support.'
                );
            }

            return ['id' => 'refund_mock_' . uniqid(), '_mock' => true];
        }

        $response = Http::withBasicAuth($this->razorpayKeyId, $this->razorpayKeySecret)
            ->post("https://api.razorpay.com/v1/payments/{$paymentId}/refund", [
                'amount' => $amount,
            ]);

        if ($response->failed()) {
            Log::error('Razorpay refund failed', ['response' => $response->json()]);
            throw new \RuntimeException('Refund failed. Please try again.');
        }

        return $response->json();
    }

    /**
     * Called by webhook after Razorpay confirms capture.
     * Credits the vet wallet and syncs the payable status.
     */
    public function creditVetWalletPublic(Payment $payment): void
    {
        $this->creditVetWallet($payment);
    }

    public function updatePayableStatusPublic(Payment $payment): void
    {
        $this->updatePayableStatus($payment);
    }

    // ─── Admin Analytics ─────────────────────────────────────────────

    public function getRevenueStats(): array
    {
        return [
            'total_revenue' => Payment::paid()->sum('amount'),
            'total_platform_fees' => Payment::paid()->sum('platform_fee'),
            'total_commission' => Payment::paid()->sum('commission_amount'),
            'total_vet_payouts' => Payment::paid()->sum('vet_payout_amount'),
            'pending_payments' => Payment::pending()->count(),
            'failed_payments' => Payment::failed()->count(),
            'total_transactions' => Payment::count(),
            'revenue_today' => Payment::paid()->whereDate('paid_at', today())->sum('amount'),
            'revenue_this_month' => Payment::paid()
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
        ];
    }

    public function getPendingPayouts(): array
    {
        return VetWallet::where('pending_payout', '>', 0)
            ->with('vetProfile:id,uuid,clinic_name,vet_name')
            ->orderByDesc('pending_payout')
            ->get()
            ->toArray();
    }

    // ─── Subscriptions ───────────────────────────────────────────────

    /**
     * Create a Razorpay order for a subscription plan purchase.
     */
    public function createSubscriptionOrder(int $userId, SubscriptionPlan $plan): Payment
    {
        return DB::transaction(function () use ($userId, $plan) {
            // Prevent duplicate pending subscription payments for the same plan
            $existing = Payment::where('payable_type', 'subscription')
                ->where('payable_id', $plan->id)
                ->where('user_id', $userId)
                ->whereIn('payment_status', ['pending', 'created', 'authorized'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \DomainException('A pending payment for this plan already exists.');
            }

            $razorpayOrder = $this->createRazorpayOrder($plan->price, 'INR');

            $payment = Payment::create([
                'payable_type'      => 'subscription',
                'payable_id'        => $plan->id,
                'user_id'           => $userId,
                'vet_profile_id'    => null,
                'razorpay_order_id' => $razorpayOrder['id'] ?? null,
                'amount'            => $plan->price,
                'platform_fee'      => $plan->price,
                'commission_amount' => 0,
                'vet_payout_amount' => 0,
                'payment_model'     => 'platform_fee',
                'payment_mode'      => 'online',
                'payment_status'    => 'created',
                'currency'          => 'INR',
                'razorpay_response' => $razorpayOrder,
            ]);

            $this->auditService->log(
                $userId,
                Payment::class,
                $payment->id,
                'created',
                null,
                ['amount' => $plan->price, 'plan_uuid' => $plan->uuid, 'razorpay_order_id' => $payment->razorpay_order_id],
                'Subscription order created'
            );

            return $payment;
        });
    }

    /**
     * Verify Razorpay subscription payment and activate the subscription.
     */
    public function verifySubscriptionPayment(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature,
        int $userId
    ): Subscription {
        $this->assertNotTestKeysInProduction();

        // Read-only checks first so failure status updates survive (see verifyPayment).
        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)
            ->where('user_id', $userId)
            ->where('payable_type', 'subscription')
            ->firstOrFail();

        if ($payment->isPaid()) {
            return Subscription::where('payment_id', $payment->id)->firstOrFail();
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            $this->razorpayKeySecret
        );
        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            Payment::where('id', $payment->id)->update([
                'payment_status' => 'failed',
                'failure_reason' => 'Signature verification failed',
            ]);
            throw new \DomainException('Payment verification failed.');
        }

        if (!$this->isMockMode()) {
            $gatewayResponse = null;
            $mismatches = $this->fetchAndCompareWithGateway($payment, $razorpayPaymentId, $razorpayOrderId, $gatewayResponse);
            if (!empty($mismatches)) {
                Payment::where('id', $payment->id)->update([
                    'payment_status' => 'failed',
                    'failure_reason' => 'Gateway reconciliation mismatch: ' . implode(',', array_keys($mismatches)),
                    'razorpay_response' => $gatewayResponse,
                ]);
                throw new \DomainException('Payment verification failed.');
            }
        }

        return DB::transaction(function () use ($payment, $razorpayPaymentId, $razorpaySignature, $userId) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($payment->isPaid()) {
                return Subscription::where('payment_id', $payment->id)->firstOrFail();
            }

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature'  => $razorpaySignature,
                'payment_status'      => 'paid',
                'paid_at'             => now(),
            ]);

            /** @var SubscriptionPlan $plan */
            $plan = SubscriptionPlan::findOrFail($payment->payable_id);

            $subscription = Subscription::create([
                'user_id'              => $userId,
                'subscription_plan_id' => $plan->id,
                'payment_id'           => $payment->id,
                'status'               => 'active',
                'starts_at'            => now(),
                'ends_at'              => now()->addDays($plan->duration_days),
            ]);

            $this->auditService->log(
                $userId,
                Subscription::class,
                $subscription->id,
                'activated',
                null,
                ['plan_uuid' => $plan->uuid, 'ends_at' => $subscription->ends_at],
                'Subscription activated'
            );

            Log::info('Subscription activated', [
                'user_id'           => $userId,
                'plan_uuid'         => $plan->uuid,
                'subscription_uuid' => $subscription->uuid,
            ]);

            return $subscription;
        });
    }
}
