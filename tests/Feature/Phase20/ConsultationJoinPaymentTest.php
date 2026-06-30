<?php

namespace Tests\Feature\Phase20;

use App\Models\ConsultationSession;
use App\Models\Payment;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-07 RED test: Consultation join() validates payment status.
 *
 * D-02 rule:
 *   - payment_id NULL  → join allowed (instant consult before Phase-22 payment wiring)
 *   - payment_id set, status != 'paid' → 422 with "Payment" in message
 *   - payment_id set, status = 'paid'  → join not blocked
 *
 * RED wave: null-join and paid-join tests will pass today (no check exists).
 * The unpaid-payment test FAILS today (returns 200 instead of 422) — that is the failing RED.
 * Plan 20-02 adds the payment check to ConsultationController::join().
 */
class ConsultationJoinPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $vetUser;
    private VetProfile $vetProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $this->vetUser->id,
        ]);
    }

    /** Helper: create a matched session for the user+vet pair. */
    private function makeMatchedSession(?int $paymentId = null): ConsultationSession
    {
        return ConsultationSession::create([
            'user_id'        => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'origin'         => 'instant',
            'modality'       => 'video',
            'status'         => 'matched',
            'matched_at'     => now(),
            'payment_id'     => $paymentId,
        ]);
    }

    /**
     * D-02: payment_id null → join must NOT be rejected on payment grounds.
     * Assert the response does not contain 'Payment' in error message if non-200.
     */
    public function test_null_payment_join_allowed(): void
    {
        $session = $this->makeMatchedSession(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/join");

        // Must not be rejected due to payment. If blocked by an unrelated domain
        // rule the response will be non-200 but should not mention "Payment".
        if ($response->status() === 422) {
            $body = $response->json('message', '');
            $this->assertStringNotContainsStringIgnoringCase('Payment', $body,
                'Null-payment join should not be blocked by a payment check (D-02).');
        } else {
            $response->assertSuccessful();
        }
    }

    /**
     * RED: unpaid payment attached → join must return 422 with "Payment" in message.
     * Currently returns 200 because payment check is not implemented.
     * 20-02 fix makes this pass.
     */
    public function test_unpaid_payment_join_blocked(): void
    {
        $payment = Payment::factory()->create([
            'user_id'        => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
            'payment_status' => 'pending',
        ]);

        $session = $this->makeMatchedSession($payment->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/join");

        $response->assertStatus(422);
        $this->assertStringContainsStringIgnoringCase(
            'Payment',
            $response->json('message', ''),
            'Error message must mention "Payment" when join is blocked by unpaid status.'
        );
    }

    /**
     * Paid payment attached → join should not be blocked by payment check.
     */
    public function test_paid_payment_join_allowed(): void
    {
        $payment = Payment::factory()->paid()->create([
            'user_id'        => $this->user->id,
            'vet_profile_id' => $this->vetProfile->id,
        ]);

        $session = $this->makeMatchedSession($payment->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/join");

        // Must not be rejected due to payment reason.
        if ($response->status() === 422) {
            $body = $response->json('message', '');
            $this->assertStringNotContainsStringIgnoringCase('Payment', $body,
                'Paid-payment join should not be blocked by payment check.');
        } else {
            $response->assertSuccessful();
        }
    }
}
