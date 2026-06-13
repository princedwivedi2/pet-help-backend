<?php

namespace Tests\Feature\Phase20;

use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-06 RED test: POST /consultations validates fee_amount against vet's stored consultation_fee.
 *
 * These tests are intentionally FAILING (RED) in Wave 0 because the production fix
 * (CRIT-01) has not yet been applied to ConsultationController::start().
 * Plan 20-02 makes them pass (GREEN).
 */
class ConsultationFeeValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $vetUser;
    private VetProfile $vetProfile;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $this->vetUser->id,
            'consultation_fee' => 500,
        ]);
        $this->pet = Pet::factory()->forUser($this->user)->create();
    }

    /**
     * RED: Fee mismatch must return 422.
     * Currently returns 201 because fee validation is not implemented.
     * 20-02 fix will make this pass.
     */
    public function test_fee_mismatch_returns_422(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/consultations', [
                'modality'   => 'video',
                'vet_uuid'   => $this->vetProfile->uuid,
                'fee_amount' => 999, // mismatches 500
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('fee_amount');
    }

    /**
     * Should pass even now: exact fee → 201.
     * This test verifies the happy path is not broken by the upcoming fix.
     */
    public function test_matching_fee_passes(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/consultations', [
                'modality'   => 'video',
                'vet_uuid'   => $this->vetProfile->uuid,
                'fee_amount' => 500, // matches
            ]);

        $response->assertStatus(201);
    }

    /**
     * Open-pool instant consult: no vet_uuid → fee check is skipped → 201.
     * D-01: fee is only validated when mobile targets a specific vet.
     * This test should remain GREEN both before and after the fix.
     */
    public function test_no_vet_uuid_skips_fee_check(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/consultations', [
                'modality' => 'video',
                // no vet_uuid, no fee_amount — open-pool instant consult
            ]);

        $response->assertStatus(201);
    }
}
