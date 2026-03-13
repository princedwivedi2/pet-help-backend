<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\SosRequest;
use App\Models\VetProfile;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Create a Razorpay order for an appointment or SOS.
     * POST /api/v1/payments/create-order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'payable_type' => 'required|in:appointment,sos',
            'payable_uuid' => 'required|string',
            'payment_model' => 'nullable|in:platform_fee,full_payment',
        ]);

        $user = $request->user();

        // Resolve the payable entity
        if ($request->payable_type === 'appointment') {
            $payable = Appointment::where('uuid', $request->payable_uuid)->first();
            if (!$payable) {
                return $this->notFound('Appointment not found');
            }
            if ($payable->user_id !== $user->id) {
                return $this->forbidden('You can only pay for your own appointments.');
            }
            $amount = $payable->fee_amount ?? $payable->vetProfile?->consultation_fee ?? 500;
            // Use home_visit_fee for home visit appointments
            if ($payable->appointment_type === 'home_visit' && $payable->vetProfile?->home_visit_fee) {
                $amount = $payable->fee_amount ?? $payable->vetProfile->home_visit_fee;
            }
        } else {
            $payable = SosRequest::where('uuid', $request->payable_uuid)->first();
            if (!$payable) {
                return $this->notFound('SOS request not found');
            }
            if ($payable->user_id !== $user->id) {
                return $this->forbidden('You can only pay for your own SOS requests.');
            }
            $amount = $payable->emergency_charge ?? 1000;
        }

        try {
            $vetProfileId = $request->payable_type === 'appointment'
                ? $payable->vet_profile_id
                : $payable->assigned_vet_id;

            $result = $this->paymentService->createOrder(
                userId: $user->id,
                vetProfileId: $vetProfileId,
                payableType: $request->payable_type === 'appointment' ? 'appointment' : 'sos_request',
                payableId: $payable->id,
                amount: $amount,
                paymentModel: $request->payment_model ?? 'platform_fee'
            );

            return $this->success('Payment order created', $result);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Verify Razorpay payment after completion.
     * POST /api/v1/payments/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'payment_uuid'           => 'required|string',
            'razorpay_payment_id'    => 'required|string',
            'razorpay_order_id'      => 'required|string',
            'razorpay_signature'     => 'required|string',
        ]);

        try {
            $payment = $this->paymentService->verifyPayment(
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_signature
            );

            return $this->success('Payment verified successfully', [
                'payment' => $payment,
            ]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Record an offline/cash payment (vet action).
     * POST /api/v1/payments/offline
     */
    public function recordOffline(Request $request): JsonResponse
    {
        $request->validate([
            'payable_type' => 'required|in:appointment,sos',
            'payable_uuid' => 'required|string',
            'amount'       => 'required|numeric|min:1',
        ]);

        $user = $request->user();

        if ($request->payable_type === 'appointment') {
            $payable = Appointment::where('uuid', $request->payable_uuid)->first();
        } else {
            $payable = SosRequest::where('uuid', $request->payable_uuid)->first();
        }

        if (!$payable) {
            return $this->notFound('Record not found');
        }

        // Only the assigned vet or admin can record offline payments
        $vetProfile = VetProfile::where('user_id', $user->id)->first();
        $payableVetId = $request->payable_type === 'sos'
            ? $payable->assigned_vet_id
            : $payable->vet_profile_id;
        if (!$user->isAdmin() && (!$vetProfile || $payableVetId !== $vetProfile->id)) {
            return $this->forbidden('Only the assigned vet or admin can record offline payments.');
        }

        try {
            $vetProfileId = $request->payable_type === 'sos'
                ? $payable->assigned_vet_id
                : $payable->vet_profile_id;

            $payment = $this->paymentService->recordOfflinePayment(
                userId: $payable->user_id,
                vetProfileId: $vetProfileId,
                payableType: $request->payable_type === 'appointment' ? 'appointment' : 'sos_request',
                payableId: $payable->id,
                amount: (float) $request->amount
            );

            return $this->success('Offline payment recorded', ['payment' => $payment]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Get payment history for the authenticated user.
     * GET /api/v1/payments
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $payments = Payment::where('user_id', $user->id)
            ->with(['vetProfile:id,uuid,clinic_name,vet_name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success('Payments retrieved', [
            'payments' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    /**
     * Get single payment details.
     * GET /api/v1/payments/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $payment = Payment::where('uuid', $uuid)
            ->with(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name'])
            ->first();

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        $user = auth()->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();
        $isPaymentVet = $vetProfile && $payment->vet_profile_id === $vetProfile->id;
        if ($payment->user_id !== $user->id && !$user->isAdmin() && !$isPaymentVet) {
            return $this->forbidden('Access denied');
        }

        return $this->success('Payment retrieved', ['payment' => $payment]);
    }

    /**
     * Request a refund.
     * POST /api/v1/payments/{uuid}/refund
     */
    public function refund(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $payment = Payment::where('uuid', $uuid)->first();

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        $user = $request->user();
        if ($payment->user_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('Access denied');
        }

        try {
            $payment = $this->paymentService->refund($payment, null, $request->reason);

            return $this->success('Refund initiated', ['payment' => $payment]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Get vet wallet info (for authenticated vet).
     * GET /api/v1/payments/wallet
     */
    public function wallet(Request $request): JsonResponse
    {
        $user = $request->user();
        $vetProfile = \App\Models\VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $wallet = $vetProfile->wallet;

        if (!$wallet) {
            return $this->success('No wallet yet', ['wallet' => null, 'transactions' => []]);
        }

        $transactions = $wallet->transactions()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->success('Wallet retrieved', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }
}
