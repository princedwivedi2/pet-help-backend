<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    /**
     * Purchase a subscription plan — two-step Razorpay flow.
     *
     * Step 1  POST /api/v1/subscriptions  { action: "create_order", plan_uuid: "..." }
     *         Returns a Razorpay order the client uses to collect payment.
     *
     * Step 2  POST /api/v1/subscriptions  { action: "verify", payment_uuid: "...",
     *                                       razorpay_payment_id: "...",
     *                                       razorpay_order_id: "...",
     *                                       razorpay_signature: "..." }
     *         Verifies the payment and activates the subscription.
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'action'              => 'required|in:create_order,verify',
            'plan_uuid'           => 'required_if:action,create_order|string',
            'razorpay_payment_id' => 'required_if:action,verify|string',
            'razorpay_order_id'   => 'required_if:action,verify|string',
            'razorpay_signature'  => 'required_if:action,verify|string',
        ]);

        $user = $request->user();

        if ($request->action === 'create_order') {
            $plan = SubscriptionPlan::where('uuid', $request->plan_uuid)
                ->where('is_active', true)
                ->first();

            if (!$plan) {
                return $this->notFound('Subscription plan not found or inactive');
            }

            // Guard: user already has an active subscription of the same plan type
            $hasActive = $user->subscriptions()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->whereHas('plan', fn ($q) => $q->where('type', $plan->type))
                ->exists();

            if ($hasActive) {
                return $this->error(
                    'You already have an active subscription of this type.',
                    null,
                    422
                );
            }

            try {
                $payment = $this->paymentService->createSubscriptionOrder($user->id, $plan);
            } catch (\DomainException $e) {
                return $this->error($e->getMessage(), null, 422);
            }

            return $this->success('Subscription order created', [
                'payment_uuid'      => $payment->uuid,
                'razorpay_key'      => config('services.razorpay.key_id'),
                'razorpay_order_id' => $payment->razorpay_order_id,
                'amount'            => $plan->price,
                'plan'              => $plan,
            ]);
        }

        // action === 'verify'
        try {
            $subscription = $this->paymentService->verifySubscriptionPayment(
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_signature,
                $user->id
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success('Subscription activated successfully', [
            'subscription' => $subscription->load('plan'),
        ]);
    }

    /**
     * Return the authenticated user's active subscription.
     * GET /api/v1/subscriptions/active
     */
    public function active(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription();

        if (!$subscription) {
            return $this->success('No active subscription', ['subscription' => null]);
        }

        return $this->success('Active subscription retrieved', [
            'subscription' => $subscription->load('plan'),
        ]);
    }
}
