<?php

namespace Tests\Feature\Api\V1;

use App\Models\ConsultationSession;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the online consultation foundation. Verifies the lifecycle
 * works end-to-end with the NullVideoProvider; a real provider would be wired
 * via VideoProviderInterface and tested separately.
 */
class ConsultationFoundationTest extends TestCase
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
            'online_fee' => 500,
            'accepted_species' => ['dog'],
        ]);
        $this->pet = Pet::factory()->forUser($this->user)->create(['species' => 'dog']);
    }

    public function test_user_can_start_an_instant_consultation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/consultations', [
                'modality' => 'video',
                'issue_category' => 'vomiting',
                'issue_description' => 'Vomited 3 times today',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('consultation_sessions', [
            'user_id' => $this->user->id,
            'status' => 'matching',
            'modality' => 'video',
            'issue_category' => 'vomiting',
        ]);
    }

    public function test_lifecycle_match_join_complete(): void
    {
        $session = ConsultationSession::create([
            'user_id' => $this->user->id,
            'pet_id' => $this->pet->id,
            'origin' => 'instant',
            'modality' => 'video',
            'status' => 'matching',
        ]);

        // Vet accepts
        $accept = $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/accept");
        $accept->assertOk();
        $session->refresh();
        $this->assertEquals('matched', $session->status);
        $this->assertEquals($this->vetProfile->id, $session->vet_profile_id);
        $this->assertNotNull($session->vet_no_show_check_at);
        $this->assertNotNull($session->room_id);  // NullVideoProvider returns one

        // Both parties join — status transitions to active
        $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/join")->assertOk();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/join")->assertOk();

        $session->refresh();
        $this->assertEquals('active', $session->status);
        $this->assertNotNull($session->started_at);

        // Complete with notes
        $complete = $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("/api/v1/consultations/{$session->uuid}/complete", [
                'vet_notes' => 'Stable, advised hydration.',
                'diagnosis' => 'Mild gastritis',
            ]);
        $complete->assertOk();
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertEquals('Mild gastritis', $session->diagnosis);
    }

    public function test_connection_failures_threshold_auto_fails_session(): void
    {
        $session = ConsultationSession::create([
            'user_id' => $this->user->id,
            'pet_id' => $this->pet->id,
            'vet_profile_id' => $this->vetProfile->id,
            'origin' => 'instant',
            'modality' => 'video',
            'status' => 'matched',
            'matched_at' => now(),
        ]);

        // 3 failures = auto-refund
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/v1/consultations/{$session->uuid}/connection-failure")
                ->assertOk();
        }

        $session->refresh();
        $this->assertEquals('failed', $session->status);
        $this->assertTrue($session->auto_refund_triggered);
        $this->assertEquals('connection_failures_exceeded', $session->refund_reason);
    }

    public function test_no_show_watchdog_expires_session_after_10_min(): void
    {
        $session = ConsultationSession::create([
            'user_id' => $this->user->id,
            'pet_id' => $this->pet->id,
            'vet_profile_id' => $this->vetProfile->id,
            'origin' => 'instant',
            'modality' => 'video',
            'status' => 'matched',
            'matched_at' => now()->subMinutes(11),
            'vet_no_show_check_at' => now()->subMinute(),  // already past deadline
        ]);

        (new \App\Jobs\ConsultationNoShowWatchdogJob())->handle(
            app(\App\Services\ConsultationService::class),
            app(\App\Services\PaymentService::class),
        );

        $session->refresh();
        $this->assertEquals('expired', $session->status);
        $this->assertTrue($session->auto_refund_triggered);
        $this->assertEquals('vet_no_show_10min', $session->refund_reason);
    }

    public function test_non_participant_cannot_view_consultation(): void
    {
        $session = ConsultationSession::create([
            'user_id' => $this->user->id,
            'origin' => 'instant', 'modality' => 'video', 'status' => 'matching',
        ]);
        $stranger = User::factory()->create(['role' => 'user']);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/consultations/{$session->uuid}")
            ->assertStatus(403);
    }
}
