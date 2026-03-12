<?php

namespace App\Services;

use App\Models\Payment;
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
     */
    public function verifyPayment(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): Payment {
        return DB::transaction(function () use ($razorpayOrderId, $razorpayPaymentId, $razorpaySignature) {
            $payment = Payment::where('razorpay_order_id', $razorpayOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            // Verify signature
            $expectedSignature = hash_hmac(
                'sha256',
                $razorpayOrderId . '|' . $razorpayPaymentId,
                $this->razorpayKeySecret
            );

            if (!hash_equals($expectedSignature, $razorpaySignature)) {
                $payment->update([
                    'payment_status' => 'failed',
                    'failure_reason' => 'Signature verification failed',
                ]);
                throw new \DomainException('Payment verification failed.');
            }

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            // Credit vet wallet
            if ($payment->vet_profile_id && $payment->vet_payout_amount > 0) {
                $this->creditVetWallet($payment);
            }

            // Update appointment/SOS payment status
            $this->updatePayableStatus($payment);

            $this->auditService->log(
                $payment->user_id,
                Payment::class,
                $payment->id,
                'payment_captured',
                null,
                ['razorpay_payment_id' => $razorpayPaymentId, 'amount' => $payment->amount],
                'Payment captured successfully'
            );

            Log::info('Payment captured', [
                'payment_uuid' => $payment->uuid,
                'razorpay_payment_id' => $razorpayPaymentId,
                'amount' => $payment->amount,
            ]);

            return $payment;
        });
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

            // Call Razorpay refund API
            if ($payment->razorpay_payment_id) {
                $this->createRazorpayRefund($payment->razorpay_payment_id, $refundAmount);
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
        $wallet = VetWallet::where('vet_profile_id', $payment->vet_profile_id)->first();
        if (!$wallet) return;

        $debitAmount = min($refundAmount, $wallet->balance);
        $wallet->decrement('balance', $debitAmount);
        $wallet->decrement('pending_payout', min($debitAmount, $wallet->pending_payout));

        WalletTransaction::create([
            'vet_profile_id' => $payment->vet_profile_id,
            'payment_id' => $payment->id,
            'type' => 'refund_debit',
            'amount' => $debitAmount,
            'balance_after' => $wallet->fresh()->balance,
            'description' => "Refund for payment #{$payment->uuid}",
        ]);
    }

    private function updatePayableStatus(Payment $payment): void
    {
        if ($payment->payable_type === 'appointment' || $payment->payable_type === \App\Models\Appointment::class) {
            \App\Models\Appointment::where('id', $payment->payable_id)
                ->update(['payment_status' => 'paid']);
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

    private function createRazorpayOrder(int $amount, string $currency): array
    {
        if (empty($this->razorpayKeyId) || empty($this->razorpayKeySecret)) {
            // Dev/test mode — return mock order
            return [
                'id' => 'order_mock_' . uniqid(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'created',
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
        if (empty($this->razorpayKeyId) || empty($this->razorpayKeySecret)) {
            return ['id' => 'refund_mock_' . uniqid()];
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
}
