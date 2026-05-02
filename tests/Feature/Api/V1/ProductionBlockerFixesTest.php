<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\WebhookEvent;
use App\Services\AuditService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the 6 production blocker fixes:
 *   1. verifyPayment calls Razorpay gateway and asserts status/amount/currency/order_id
 *   2. webhook_events idempotency
 *   3. Production rejects rzp_test_* keys
 *   4. VetProfile $fillable hardening (no self-approval)
 *   5. admin:create command exists and works (idempotent)
 *   6. .env.example has APP_DEBUG=false (file content check)
 */
class ProductionBlockerFixesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Fix 1: Gateway-side verifyPayment ───────────────────────────────────

    /** @test */
    public function verify_payment_calls_gateway_and_succeeds_when_all_fields_match(): void
    {
        $service = $this->makeServiceWithLiveKeys();
        $payment = $this->makeOnlinePayment(amount: 50000, currency: 'INR');

        Http::fake([
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_LIVE123',
                'status' => 'captured',
                'amount' => 50000,
                'currency' => 'INR',
                'order_id' => $payment->razorpay_order_id,
            ], 200),
        ]);

        $result = $service->verifyPayment(
            $payment->razorpay_order_id,
            'pay_LIVE123',
            $this->signature($payment->razorpay_order_id, 'pay_LIVE123')
        );

        $this->assertEquals('paid', $result->payment_status);
        $this->assertNotNull($result->paid_at);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'api.razorpay.com/v1/payments/pay_LIVE123'));
    }

    /** @test */
    public function verify_payment_rejects_status_not_captured(): void
    {
        $service = $this->makeServiceWithLiveKeys();
        $payment = $this->makeOnlinePayment(amount: 50000);

        Http::fake([
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_X', 'status' => 'authorized',  // not captured
                'amount' => 50000, 'currency' => 'INR',
                'order_id' => $payment->razorpay_order_id,
            ], 200),
        ]);

        $this->expectException(\DomainException::class);
        try {
            $service->verifyPayment(
                $payment->razorpay_order_id, 'pay_X',
                $this->signature($payment->razorpay_order_id, 'pay_X')
            );
        } finally {
            $this->assertEquals('failed', $payment->fresh()->payment_status);
        }
    }

    /** @test */
    public function verify_payment_rejects_amount_mismatch(): void
    {
        $service = $this->makeServiceWithLiveKeys();
        $payment = $this->makeOnlinePayment(amount: 50000);

        Http::fake([
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_X', 'status' => 'captured',
                'amount' => 1,  // tampered: ₹0.01 instead of ₹500
                'currency' => 'INR',
                'order_id' => $payment->razorpay_order_id,
            ], 200),
        ]);

        $this->expectException(\DomainException::class);
        try {
            $service->verifyPayment(
                $payment->razorpay_order_id, 'pay_X',
                $this->signature($payment->razorpay_order_id, 'pay_X')
            );
        } finally {
            $this->assertEquals('failed', $payment->fresh()->payment_status);
        }
    }

    /** @test */
    public function verify_payment_rejects_currency_mismatch(): void
    {
        $service = $this->makeServiceWithLiveKeys();
        $payment = $this->makeOnlinePayment(amount: 50000, currency: 'INR');

        Http::fake([
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_X', 'status' => 'captured',
                'amount' => 50000, 'currency' => 'USD',
                'order_id' => $payment->razorpay_order_id,
            ], 200),
        ]);

        $this->expectException(\DomainException::class);
        $service->verifyPayment(
            $payment->razorpay_order_id, 'pay_X',
            $this->signature($payment->razorpay_order_id, 'pay_X')
        );
    }

    /** @test */
    public function verify_payment_rejects_order_id_mismatch(): void
    {
        $service = $this->makeServiceWithLiveKeys();
        $payment = $this->makeOnlinePayment(amount: 50000);

        Http::fake([
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_X', 'status' => 'captured',
                'amount' => 50000, 'currency' => 'INR',
                'order_id' => 'order_DIFFERENT',  // replay from a different order
            ], 200),
        ]);

        $this->expectException(\DomainException::class);
        $service->verifyPayment(
            $payment->razorpay_order_id, 'pay_X',
            $this->signature($payment->razorpay_order_id, 'pay_X')
        );
    }

    // ─── Fix 2: webhook idempotency ──────────────────────────────────────────

    /** @test */
    public function webhook_processes_event_once_and_ignores_replay(): void
    {
        Config::set('services.razorpay.webhook_secret', 'whsec_test');

        $vetUser = User::factory()->create(['role' => 'vet']);
        $vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $vetUser->id, 'consultation_fee' => 500,
        ]);
        $user = User::factory()->create(['role' => 'user']);
        $pet = Pet::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forUser($user)->forVet($vetProfile)
            ->completed()->create(['pet_id' => $pet->id]);

        $payment = Payment::factory()->fullPayment()->create([
            'user_id' => $user->id,
            'vet_profile_id' => $vetProfile->id,
            'payable_type' => 'appointment',
            'payable_id' => $appt->id,
            'amount' => 50000,
            'razorpay_order_id' => 'order_X',
            'payment_status' => 'created',
        ]);

        $payload = json_encode([
            'id' => 'evt_unique_1',
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_X',
                'order_id' => 'order_X',
            ]]],
        ]);
        $signature = hash_hmac('sha256', $payload, 'whsec_test');

        // First delivery
        $r1 = $this->call('POST', '/api/payments/webhook',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Razorpay-Signature' => $signature],
            $payload
        );
        $r1->assertOk();

        $this->assertEquals('paid', $payment->fresh()->payment_status);
        $this->assertEquals(1, WebhookEvent::where('event_id', 'evt_unique_1')->count());
        $vetCreditsAfterFirst = \App\Models\WalletTransaction::where('payment_id', $payment->id)
            ->where('type', 'credit')->count();

        // Replay — same payload, same signature, same event_id
        $r2 = $this->call('POST', '/api/payments/webhook',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Razorpay-Signature' => $signature],
            $payload
        );
        $r2->assertOk();

        // Idempotency: still exactly one webhook_events row, still exactly one credit.
        $this->assertEquals(1, WebhookEvent::where('event_id', 'evt_unique_1')->count());
        $this->assertEquals(
            $vetCreditsAfterFirst,
            \App\Models\WalletTransaction::where('payment_id', $payment->id)->where('type', 'credit')->count(),
            'Replay must not double-credit the vet wallet'
        );
    }

    /** @test */
    public function webhook_rejects_invalid_signature(): void
    {
        Config::set('services.razorpay.webhook_secret', 'whsec_test');

        $payload = json_encode(['id' => 'evt_x', 'event' => 'payment.captured', 'payload' => []]);

        $r = $this->call('POST', '/api/payments/webhook',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-Razorpay-Signature' => 'wrong'],
            $payload
        );
        $r->assertStatus(400);
        $this->assertEquals(0, WebhookEvent::count());
    }

    // ─── Fix 3: Production rejects rzp_test_* keys ───────────────────────────

    /** @test */
    public function production_environment_rejects_test_keys(): void
    {
        Config::set('app.env', 'production');
        Config::set('services.razorpay.key_id', 'rzp_test_FakeLiveDeploy');
        Config::set('services.razorpay.key_secret', 'secret_test');

        $service = new PaymentService(app(AuditService::class));

        $this->assertFalse($service->isConfigured(),
            'isConfigured must return false when test keys are used in production');

        $payment = $this->makeOnlinePayment(amount: 50000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment gateway misconfigured');
        $service->verifyPayment(
            $payment->razorpay_order_id, 'pay_X',
            $this->signature($payment->razorpay_order_id, 'pay_X')
        );
    }

    /** @test */
    public function non_production_environments_accept_test_keys(): void
    {
        Config::set('app.env', 'local');
        Config::set('services.razorpay.key_id', 'rzp_test_AbCdEfGhIjKlMn');
        Config::set('services.razorpay.key_secret', 'secret_test');

        $service = new PaymentService(app(AuditService::class));
        // Test keys in local = mock mode (acceptable for dev)
        $this->assertTrue($service->isMockMode());
    }

    // ─── Fix 4: VetProfile $fillable hardening ───────────────────────────────

    /** @test */
    public function vet_status_is_not_mass_assignable(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->create(['user_id' => $vetUser->id]);

        $this->assertEquals('pending', $vet->vet_status, 'fixture starts pending');

        // Simulate a malicious profile-update payload trying to self-approve.
        $vet->update([
            'clinic_name' => 'Updated Clinic',
            'vet_status' => 'approved',
            'verification_status' => 'approved',
            'is_active' => true,
        ]);

        $vet->refresh();
        $this->assertEquals('Updated Clinic', $vet->clinic_name, 'legitimate field still updates');
        $this->assertEquals('pending', $vet->vet_status, 'protected status field is NOT mass-assignable');
        $this->assertEquals('pending', $vet->verification_status);
    }

    /** @test */
    public function force_fill_still_works_for_admin_gated_transitions(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->create(['user_id' => $vetUser->id]);

        // Service-layer admin transition uses forceFill — still works.
        $vet->forceFill(['vet_status' => 'approved'])->save();

        $this->assertEquals('approved', $vet->fresh()->vet_status);
    }

    // ─── Fix 5: admin:create command ─────────────────────────────────────────

    /** @test */
    public function admin_create_command_creates_admin_when_missing(): void
    {
        $this->assertEquals(0, User::where('email', 'newadmin@test.com')->count());

        $exit = Artisan::call('admin:create', [
            '--email' => 'newadmin@test.com',
            '--name' => 'New Admin',
            '--password' => 'StrongPassword123',
        ]);

        $this->assertEquals(0, $exit, 'command exits 0 on success');
        $admin = User::where('email', 'newadmin@test.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);
        $this->assertNotNull($admin->email_verified_at);
    }

    /** @test */
    public function admin_create_command_is_idempotent(): void
    {
        Artisan::call('admin:create', [
            '--email' => 'dupe@test.com', '--password' => 'StrongPassword123',
        ]);
        $exit = Artisan::call('admin:create', [
            '--email' => 'dupe@test.com', '--password' => 'StrongPassword123',
        ]);

        $this->assertEquals(0, $exit, 'second run is a no-op success');
        $this->assertEquals(1, User::where('email', 'dupe@test.com')->count());
    }

    /** @test */
    public function admin_create_command_rejects_short_password(): void
    {
        $exit = Artisan::call('admin:create', [
            '--email' => 'shortpw@test.com', '--password' => 'short',
        ]);

        $this->assertEquals(1, $exit, 'rejects passwords < 8 chars');
        $this->assertEquals(0, User::where('email', 'shortpw@test.com')->count());
    }

    // ─── Fix 6: APP_DEBUG=false in .env.example ──────────────────────────────

    /** @test */
    public function env_example_has_app_debug_false(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));
        $this->assertNotFalse($envExample, '.env.example must exist');
        $this->assertStringContainsString('APP_DEBUG=false', $envExample,
            '.env.example must default APP_DEBUG=false to avoid stack trace leaks in production');
        $this->assertStringNotContainsString("\nAPP_DEBUG=true", "\n" . $envExample,
            '.env.example must not have APP_DEBUG=true uncommented');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeServiceWithLiveKeys(): PaymentService
    {
        Config::set('app.env', 'local');
        Config::set('services.razorpay.key_id', 'rzp_live_FakeKey1234');
        Config::set('services.razorpay.key_secret', 'live_secret_xyz');
        return new PaymentService(app(AuditService::class));
    }

    private function makeOnlinePayment(int $amount = 50000, string $currency = 'INR'): Payment
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vetProfile = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $user = User::factory()->create(['role' => 'user']);
        $pet = Pet::factory()->forUser($user)->create();
        $appt = Appointment::factory()->forUser($user)->forVet($vetProfile)
            ->completed()->create(['pet_id' => $pet->id]);

        return Payment::factory()->create([
            'user_id' => $user->id,
            'vet_profile_id' => $vetProfile->id,
            'payable_type' => 'appointment',
            'payable_id' => $appt->id,
            'razorpay_order_id' => 'order_TEST_' . uniqid(),
            'amount' => $amount,
            'currency' => $currency,
            'payment_status' => 'created',
        ]);
    }

    private function signature(string $orderId, string $paymentId): string
    {
        return hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            config('services.razorpay.key_secret')
        );
    }
}
