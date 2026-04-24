<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\DispatchSosNearbyVetsJob;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SosTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/sos';

    private function authUser(): User
    {
        return User::factory()->create();
    }

    private function validSosPayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'description' => 'My pet needs urgent help immediately please.',
            'emergency_type' => 'injury',
        ], $overrides);
    }

    // ─── Create SOS ─────────────────────────────────────────────────

    public function test_create_sos_success(): void
    {
        Queue::fake();

        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload([
                'pet_id' => $pet->id,
            ]));

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['sos' => ['uuid', 'status']]]);

        $this->assertDatabaseHas('sos_requests', [
            'user_id' => $user->id,
            'status' => 'sos_pending',
        ]);

        Queue::assertPushed(DispatchSosNearbyVetsJob::class, 1);

        // Verify incident log was auto-created
        $this->assertDatabaseHas('incident_logs', [
            'user_id' => $user->id,
            'incident_type' => 'emergency',
        ]);
    }

    public function test_create_sos_without_pet(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload());

        $response->assertStatus(201);
    }

    public function test_create_sos_unauthenticated(): void
    {
        $this->postJson($this->prefix, $this->validSosPayload())
            ->assertStatus(401);
    }

    public function test_create_sos_validation_short_description(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload([
                'description' => 'short',
            ]));

        $response->assertStatus(422);
    }

    public function test_create_sos_blocked_by_active_sos(): void
    {
        $user = $this->authUser();
        SosRequest::factory()->forUser($user)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload());

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_create_sos_other_users_pet_rejected(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $otherPet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload([
                'pet_id' => $otherPet->id,
            ]));

        $response->assertStatus(422);
    }

    public function test_create_sos_rate_limit(): void
    {
        $user = $this->authUser();

        // Create 5 SOS requests (cancel each so the next can be created)
        for ($i = 0; $i < 5; $i++) {
            $sos = SosRequest::factory()->forUser($user)->pending()->create();
            $sos->update(['status' => 'cancelled']);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, $this->validSosPayload());

        $response->assertStatus(429)
            ->assertJson(['success' => false]);
    }

    // ─── Active SOS ──────────────────────────────────────────────────

    public function test_active_sos_exists(): void
    {
        $user = $this->authUser();
        $sos = SosRequest::factory()->forUser($user)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['sos' => ['uuid' => $sos->uuid]],
            ]);
    }

    public function test_active_sos_none(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson(['data' => ['sos' => null]]);
    }

    // ─── Update Status ───────────────────────────────────────────────

    public function test_cancel_pending_sos(): void
    {
        $user = $this->authUser();
        $sos = SosRequest::factory()->forUser($user)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'cancelled']]]);
    }

    public function test_complete_acknowledged_sos(): void
    {
        $user = $this->authUser();
        $sos = SosRequest::factory()->forUser($user)->acknowledged()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'completed',
                'resolution_notes' => 'Vet arrived and treated the pet.',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'completed']]]);
    }

    public function test_cannot_complete_pending_sos(): void
    {
        $user = $this->authUser();
        $sos = SosRequest::factory()->forUser($user)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_completed_sos(): void
    {
        $user = $this->authUser();
        $sos = SosRequest::factory()->forUser($user)->completed()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(422);
    }

    public function test_update_other_users_sos(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $sos = SosRequest::factory()->forUser($other)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(404);
    }

    public function test_unassigned_vet_cannot_update_post_acceptance_status(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $assignedVetUser = User::factory()->create(['role' => 'vet']);
        $unassignedVetUser = User::factory()->create(['role' => 'vet']);

        $assignedVet = VetProfile::factory()->verified()->create(['user_id' => $assignedVetUser->id]);
        VetProfile::factory()->verified()->create(['user_id' => $unassignedVetUser->id]);

        $sos = SosRequest::factory()->forUser($owner)->create([
            'status' => 'sos_accepted',
            'assigned_vet_id' => $assignedVet->id,
        ]);

        $response = $this->actingAs($unassignedVetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'vet_on_the_way',
                'vet_latitude' => 40.71,
                'vet_longitude' => -74.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_unassigned_vet_cannot_update_location(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $assignedVetUser = User::factory()->create(['role' => 'vet']);
        $unassignedVetUser = User::factory()->create(['role' => 'vet']);

        $assignedVet = VetProfile::factory()->verified()->create(['user_id' => $assignedVetUser->id]);
        VetProfile::factory()->verified()->create(['user_id' => $unassignedVetUser->id]);

        $sos = SosRequest::factory()->forUser($owner)->create([
            'status' => 'sos_accepted',
            'assigned_vet_id' => $assignedVet->id,
        ]);

        $response = $this->actingAs($unassignedVetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/location", [
                'latitude' => 40.71,
                'longitude' => -74.00,
            ]);

        $response->assertStatus(403);
    }
}
