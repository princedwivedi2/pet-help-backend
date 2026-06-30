<?php

namespace Tests\Feature\Phase20;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetWallet;
use App\Models\WalletTransaction;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * BE-08 RED test: Webhook idempotency — duplicate payment.captured events must
 * credit the vet wallet exactly once.
 *
 * The existing webhook_events UNIQUE constraint prevents duplicate event rows.
 * The remaining race is: two concurrent requests both pass the event-insert check
 * before either updates the Payment row to 'paid', causing double wallet credit.
 *
 * RED wave: This test might pass today IF the UNIQUE dedup on event_id is enough
 * to prevent double-credit (same event_id → second insert throws → second request
 * returns without updating). The true race (different event_ids, same order_id)
 * is tested separately. Plan 20-02 adds lockForUpdate() to fully close the gap.
 *
 * Route: POST /api/v1/payments/webhook (public, HMAC verified)
 * Route file: POST api/payments/webhook (no /v1/ prefix — confirmed from routes/api_v1.php).
 */
class PaymentWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_phase20_test';
    private const WEBHOOK_ROUTE  = '/api/v1/payments/webhook';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.razorpay.webhook_secret', self::WEBHOOK_SECRET);
    }

    /** Build a signed webhook payload and return [$jsonBody, $signature]. */
    private function buildPayload(string $eventId, string $orderId, string $paymentId): array
    {
        $body = json_encode([
            'id'    => $eventId,
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id'       => $paymentId,
                        'order_id' => $orderId,
                    ],
                ],
            ],
        ]);
        $signature = hash_hmac('sha256', $body, self::WEBHOOK_SECRET);
        return [$body, $signature];
    }

    private function postWebhook(string $body, string $signature)
    {
        return $this->call(
            'POST',
            self::WEBHOOK_ROUTE,
            [], [], [],
            [
                'CONTENT_TYPE'               => 'application/json',
                'HTTP_X-Razorpay-Signature'  => $signature,
            ],
            $body
        );
    }

    /**
     * RED: Two deliveries of the same event_id must result in exactly one
     * wallet credit (vet payout credited once, not twice).
     *
     * Today: may or may not fail depending on whether event-row dedup blocks
     * the second payment.update. The lockForUpdate() fix in 20-02 guarantees it.
     */
    public function test_duplicate_capture_credits_once(): void
    {
        $vetUser    = User::factory()->create(['role' => 'vet']);
        $vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $vetUser->id,
        ]);
        $user = User::factory()->create(['role' => 'user']);
        $pet  = Pet::factory()->forUser($user)->create();
        $appt = Appointment::factory()
            ->forUser($user)
            ->forVet($vetProfile)
            ->completed()
            ->create(['pet_id' => $pet->id]);

        $payment = Payment::factory()->fullPayment()->create([
            'user_id'           => $user->id,
            'vet_profile_id'    => $vetProfile->id,
            'payable_type'      => 'appointment',
            'payable_id'        => $appt->id,
            'amount'            => 50000,
            'vet_payout_amount' => 42500, // non-zero so creditVetWallet fires
            'razorpay_order_id' => 'order_phase20_test',
            'payment_status'    => 'pending',
        ]);

        [$body, $sig] = $this->buildPayload(
            'evt_phase20_idem_001',
            'order_phase20_test',
            'pay_phase20_001'
        );

        // First delivery
        $r1 = $this->postWebhook($body, $sig);
        $r1->assertOk();

        // Second delivery — same event_id (Razorpay retry / replay)
        $r2 = $this->postWebhook($body, $sig);
        $r2->assertOk();

        // Assertions
        $payment->refresh();
        $this->assertEquals('paid', $payment->payment_status,
            'Payment must be marked paid after a captured event.');

        // Exactly one WebhookEvent row
        $this->assertEquals(
            1,
            WebhookEvent::where('event_id', 'evt_phase20_idem_001')->count(),
            'Exactly one webhook_events row must exist (second insert must be deduped).'
        );

        // Exactly one wallet credit transaction
        $creditCount = WalletTransaction::where('vet_profile_id', $vetProfile->id)
            ->where('type', 'credit')
            ->count();

        $this->assertEquals(1, $creditCount,
            'Vet wallet must be credited exactly once — duplicate event must not double-credit.');
    }
}
