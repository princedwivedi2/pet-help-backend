<?php

namespace Tests\Feature\Api\V1;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Subscription Purchase Flow Tests
 * Covers: POST /api/v1/subscriptions (create_order & verify) and GET /api/v1/subscriptions/active
 */
class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/subscriptions';
    private User $user;
    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);

        $this->plan = SubscriptionPlan::create([
            'name'          => 'Basic Plan',
            'type'          => 'user',
            'price'         => 49900, // ₹499 in paise
            'duration_days' => 30,
            'is_active'     => true,
        ]);
    }

    // ─── 1. Create Order ─────────────────────────────────────────────

    public function test_user_can_create_subscription_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'    => 'create_order',
                'plan_uuid' => $this->plan->uuid,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['payment_uuid', 'razorpay_order_id', 'amount', 'plan'],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id'      => $this->user->id,
            'payable_type' => 'subscription',
            'payable_id'   => $this->plan->id,
            'payment_status' => 'created',
        ]);
    }

    public function test_cannot_create_order_for_inactive_plan(): void
    {
        $this->plan->update(['is_active' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'    => 'create_order',
                'plan_uuid' => $this->plan->uuid,
            ]);

        $response->assertStatus(404);
    }

    public function test_cannot_create_order_when_already_subscribed(): void
    {
        // Activate an existing subscription for this user and plan type
        Subscription::create([
            'user_id'              => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'status'               => 'active',
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'    => 'create_order',
                'plan_uuid' => $this->plan->uuid,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_create_order_requires_plan_uuid(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action' => 'create_order',
            ]);

        $response->assertStatus(422);
    }

    // ─── 2. Verify Payment ───────────────────────────────────────────

    public function test_verify_activates_subscription_in_mock_mode(): void
    {
        // Create the order first
        $orderResp = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'    => 'create_order',
                'plan_uuid' => $this->plan->uuid,
            ]);

        $orderResp->assertOk();
        $razorpayOrderId = $orderResp->json('data.razorpay_order_id');

        // Build a valid HMAC signature using test secret (empty in test env)
        $testSecret = config('services.razorpay.key_secret', '');
        $fakePaymentId = 'pay_testfake123';
        $signature = hash_hmac('sha256', $razorpayOrderId . '|' . $fakePaymentId, $testSecret);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'              => 'verify',
                'razorpay_order_id'   => $razorpayOrderId,
                'razorpay_payment_id' => $fakePaymentId,
                'razorpay_signature'  => $signature,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['subscription']]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id'              => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'status'               => 'active',
        ]);
    }

    public function test_verify_fails_with_bad_signature(): void
    {
        $orderResp = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'    => 'create_order',
                'plan_uuid' => $this->plan->uuid,
            ]);

        $orderResp->assertOk();
        $razorpayOrderId = $orderResp->json('data.razorpay_order_id');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'              => 'verify',
                'razorpay_order_id'   => $razorpayOrderId,
                'razorpay_payment_id' => 'pay_fake',
                'razorpay_signature'  => 'invalidsignature',
            ]);

        $response->assertStatus(422);
    }

    public function test_verify_is_idempotent(): void
    {
        // Activate a subscription and link a paid payment
        $payment = \App\Models\Payment::create([
            'payable_type'      => 'subscription',
            'payable_id'        => $this->plan->id,
            'user_id'           => $this->user->id,
            'razorpay_order_id' => 'order_idempotenttest',
            'amount'            => $this->plan->price,
            'platform_fee'      => $this->plan->price,
            'commission_amount' => 0,
            'vet_payout_amount' => 0,
            'payment_model'     => 'platform_fee',
            'payment_mode'      => 'online',
            'payment_status'    => 'paid',
            'paid_at'           => now(),
        ]);

        Subscription::create([
            'user_id'              => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'payment_id'           => $payment->id,
            'status'               => 'active',
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
        ]);

        $testSecret = config('services.razorpay.key_secret', '');
        $fakePaymentId = 'pay_idempotent';
        $signature = hash_hmac('sha256', 'order_idempotenttest' . '|' . $fakePaymentId, $testSecret);

        // Second verify call should be idempotent (returns existing subscription)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'action'              => 'verify',
                'razorpay_order_id'   => 'order_idempotenttest',
                'razorpay_payment_id' => $fakePaymentId,
                'razorpay_signature'  => $signature,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Still only one subscription
        $this->assertDatabaseCount('subscriptions', 1);
    }

    // ─── 3. Active Subscription ─────────────────────────────────────

    public function test_user_can_get_active_subscription(): void
    {
        Subscription::create([
            'user_id'              => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'status'               => 'active',
            'starts_at'            => now(),
            'ends_at'              => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['subscription']]);
    }

    public function test_returns_null_when_no_active_subscription(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson(['data' => ['subscription' => null]]);
    }

    public function test_unauthenticated_user_cannot_access_subscriptions(): void
    {
        $this->postJson($this->prefix, [
            'action'    => 'create_order',
            'plan_uuid' => $this->plan->uuid,
        ])->assertStatus(401);

        $this->getJson("{$this->prefix}/active")->assertStatus(401);
    }
}
