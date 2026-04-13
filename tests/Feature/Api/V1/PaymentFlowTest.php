<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Flow QA Test
 * Covers: Create Order, Verify, Offline Payment, Wallet, Refund
 */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/payments';
    private User $user;
    private User $vetUser;
    private VetProfile $vetProfile;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $this->vetUser->id,
            'consultation_fee' => 500,
            'home_visit_fee' => 800,
        ]);
        $pet = Pet::factory()->forUser($this->user)->create();
        $this->appointment = Appointment::factory()
            ->forUser($this->user)
            ->forVet($this->vetProfile)
            ->completed()
            ->create(['pet_id' => $pet->id]);
    }

    // ─── 1. Create Payment Order ─────────────────────────────────────

    public function test_user_can_create_payment_order_for_appointment(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
                'payment_model' => 'platform_fee',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'payable_type' => 'appointment',
            'payable_id' => $this->appointment->id,
            'payment_status' => 'created',
        ]);
    }

    public function test_user_can_create_payment_for_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->completed()->create([
            'assigned_vet_id' => $this->vetProfile->id,
            'emergency_charge' => 1000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'sos',
                'payable_uuid' => $sos->uuid,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'payable_type' => 'sos_request',
            'payable_id' => $sos->id,
        ]);
    }

    public function test_cannot_create_order_for_other_users_appointment(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
            ]);

        $response->assertStatus(403);
    }

    public function test_duplicate_payment_prevented(): void
    {
        // Create first payment
        $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
            ])->assertOk();

        // Try duplicate
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
            ]);

        $response->assertStatus(422);
    }

    public function test_invalid_payable_type_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'invalid',
                'payable_uuid' => $this->appointment->uuid,
            ]);

        $response->assertStatus(422);
    }

    // ─── 2. Offline Payment ──────────────────────────────────────────

    public function test_vet_can_record_offline_payment(): void
    {
        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("{$this->prefix}/offline", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
                'amount' => 500,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'payable_id' => $this->appointment->id,
            'payment_mode' => 'offline',
            'payment_status' => 'paid',
            'amount' => 500,
        ]);
    }

    public function test_non_assigned_vet_cannot_record_offline(): void
    {
        $otherVetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $otherVetUser->id]);

        $response = $this->actingAs($otherVetUser, 'sanctum')
            ->postJson("{$this->prefix}/offline", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
                'amount' => 500,
            ]);

        $response->assertStatus(403);
    }

    public function test_record_offline_payment_for_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->completed()->create([
            'assigned_vet_id' => $this->vetProfile->id,
        ]);

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("{$this->prefix}/offline", [
                'payable_type' => 'sos',
                'payable_uuid' => $sos->uuid,
                'amount' => 1000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('payments', [
            'payable_type' => 'sos_request',
            'payment_mode' => 'offline',
            'payment_status' => 'paid',
        ]);
    }

    // ─── 3. Payment History ──────────────────────────────────────────

    public function test_user_can_view_payment_history(): void
    {
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
            'payable_type' => Appointment::class,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'payments',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_user_can_view_single_payment(): void
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
            'payable_type' => Appointment::class,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/{$payment->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.payment.uuid', $payment->uuid);
    }

    public function test_other_user_cannot_view_payment(): void
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
            'payable_type' => Appointment::class,
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("{$this->prefix}/{$payment->uuid}");

        $response->assertStatus(403);
    }

    // ─── 4. Refund ───────────────────────────────────────────────────

    public function test_user_can_request_refund(): void
    {
        $payment = Payment::factory()->paid()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/{$payment->uuid}/refund", [
                'reason' => 'Vet did not show up for the appointment',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_status' => 'refunded',
        ]);
    }

    public function test_cannot_refund_unpaid_payment(): void
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
            'payment_status' => 'created',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/{$payment->uuid}/refund");

        $response->assertStatus(422);
    }

    public function test_other_user_cannot_refund(): void
    {
        $payment = Payment::factory()->paid()->create([
            'user_id' => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payable_id' => $this->appointment->id,
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("{$this->prefix}/{$payment->uuid}/refund");

        $response->assertStatus(403);
    }

    // ─── 5. Wallet ───────────────────────────────────────────────────

    public function test_vet_can_view_wallet(): void
    {
        VetWallet::create([
            'vet_profile_id' => $this->vetProfile->id,
            'balance' => 5000,
            'total_earned' => 10000,
            'total_paid_out' => 5000,
            'pending_payout' => 5000,
        ]);

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->getJson("{$this->prefix}/wallet");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['wallet', 'transactions']]);
    }

    public function test_vet_without_wallet_gets_null(): void
    {
        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->getJson("{$this->prefix}/wallet");

        $response->assertOk()
            ->assertJson(['data' => ['wallet' => null]]);
    }

    public function test_non_vet_gets_404_for_wallet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/wallet");

        $response->assertStatus(404);
    }

    // ─── 6. Fee Calculation (Platform Fee vs Full Payment) ───────────

    public function test_platform_fee_model_calculation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
                'payment_model' => 'platform_fee',
            ]);

        $response->assertOk();

        // Platform fee model: platform keeps all, vet gets nothing
        $payment = Payment::where('payable_id', $this->appointment->id)->first();
        $this->assertEquals($payment->amount, $payment->platform_fee);
        $this->assertEquals(0, $payment->vet_payout_amount);
    }

    public function test_full_payment_model_calculation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $this->appointment->uuid,
                'payment_model' => 'full_payment',
            ]);

        $response->assertOk();

        // Full payment model: 15% commission, rest to vet
        $payment = Payment::where('payable_id', $this->appointment->id)->first();
        $expectedCommission = (int) round($payment->amount * 0.15);
        $this->assertEquals($expectedCommission, $payment->commission_amount);
        $this->assertEquals($payment->amount - $expectedCommission, $payment->vet_payout_amount);
    }

    // ─── 7. Home Visit Fee Applied ───────────────────────────────────

    public function test_home_visit_uses_home_visit_fee(): void
    {
        $pet = Pet::factory()->forUser($this->user)->create();
        $homeVisitAppt = Appointment::factory()
            ->forUser($this->user)
            ->forVet($this->vetProfile)
            ->homeVisit()
            ->completed()
            ->create(['pet_id' => $pet->id, 'fee_amount' => 800]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("{$this->prefix}/create-order", [
                'payable_type' => 'appointment',
                'payable_uuid' => $homeVisitAppt->uuid,
            ]);

        $response->assertOk();

        $payment = Payment::where('payable_id', $homeVisitAppt->id)->first();
        $this->assertEquals(800, $payment->amount);
    }
}
