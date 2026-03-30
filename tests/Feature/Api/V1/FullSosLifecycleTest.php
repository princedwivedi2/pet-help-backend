<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full SOS Lifecycle QA Test
 * Flow: Create → Accept → Vet On Way → Arrived → In Progress → Complete
 */
class FullSosLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/sos';
    private User $user;
    private User $vetUser;
    private User $admin;
    private VetProfile $vetProfile;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->vetProfile = VetProfile::factory()->verified()->emergency()->create([
            'user_id' => $this->vetUser->id,
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ]);
        $this->pet = Pet::factory()->forUser($this->user)->create();
    }

    // ─── 1. Create SOS ──────────────────────────────────────────────

    public function test_user_creates_sos_with_pet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'pet_id' => $this->pet->id,
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'My pet is having severe breathing difficulty, needs immediate help!',
                'emergency_type' => 'breathing',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['sos' => ['uuid', 'status']]]);

        // SOS should be created with sos_pending (new status)
        $this->assertDatabaseHas('sos_requests', [
            'user_id' => $this->user->id,
            'status' => 'sos_pending',
            'emergency_type' => 'breathing',
        ]);

        // Incident log should be auto-created
        $this->assertDatabaseHas('incident_logs', [
            'user_id' => $this->user->id,
            'incident_type' => 'emergency',
        ]);
    }

    public function test_user_creates_sos_without_pet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'Found an injured stray animal on the road, needs urgent help!',
                'emergency_type' => 'injury',
            ]);

        $response->assertStatus(201);
    }

    public function test_sos_short_description_rejected(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'help',
                'emergency_type' => 'injury',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_second_active_sos(): void
    {
        // Create first SOS via API
        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'First emergency, my pet is injured badly!',
                'emergency_type' => 'injury',
            ])->assertStatus(201);

        // Try creating second
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'Another emergency while first is active still',
                'emergency_type' => 'illness',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_unauthenticated_cannot_create_sos(): void
    {
        $this->postJson($this->prefix, [
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'description' => 'Emergency without auth credentials!',
            'emergency_type' => 'injury',
        ])->assertStatus(401);
    }

    public function test_other_users_pet_rejected(): void
    {
        $other = User::factory()->create();
        $otherPet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'pet_id' => $otherPet->id,
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'Trying to use someone elses pet for SOS request',
                'emergency_type' => 'injury',
            ]);

        $response->assertStatus(422);
    }

    // ─── 2. Active SOS ──────────────────────────────────────────────

    public function test_user_sees_own_active_sos(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'My pet needs urgent emergency care right now!',
                'emergency_type' => 'injury',
            ])->assertStatus(201);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_no_active_sos(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/active");

        $response->assertOk()
            ->assertJson(['data' => ['sos' => null]]);
    }

    // ─── 3. Status Updates (User Can Cancel Own SOS) ─────────────────

    public function test_user_can_cancel_pending_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->pending()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'cancelled']]]);
    }

    public function test_user_can_cancel_sos_with_new_status(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->pending()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'sos_cancelled',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'sos_cancelled']]]);
    }

    public function test_cannot_complete_pending_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->pending()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_completed_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->completed()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(422);
    }

    public function test_other_user_cannot_update_sos(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->pending()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($other, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(404);
    }

    // ─── 4. Complete Acknowledged SOS ────────────────────────────────

    public function test_complete_acknowledged_sos_with_notes(): void
    {
        $sos = SosRequest::factory()->forUser($this->user)->acknowledged()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'completed',
                'resolution_notes' => 'Vet arrived and treated the pet successfully.',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'completed']]]);

        $this->assertDatabaseHas('sos_requests', [
            'id' => $sos->id,
            'status' => 'completed',
        ]);
    }

    // ─── 5. Full Lifecycle via Admin (admin can update any SOS) ──────

    public function test_admin_full_sos_lifecycle(): void
    {
        // Step 1: User creates SOS
        $createResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'Full lifecycle test - pet has severe seizure emergency!',
                'emergency_type' => 'seizure',
            ]);

        $createResponse->assertStatus(201);
        $uuid = $createResponse->json('data.sos.uuid');
        $this->assertNotNull($uuid);

        // Step 2: Admin accepts (assigning vet)
        $acceptResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/{$uuid}/status", [
                'status' => 'sos_accepted',
                'response_type' => 'home_visit',
                'estimated_arrival_at' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
            ]);
        $acceptResponse->assertOk();

        // Step 3: Admin updates to vet_on_the_way
        $onWayResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/{$uuid}/status", [
                'status' => 'vet_on_the_way',
                'vet_latitude' => 40.7100,
                'vet_longitude' => -74.010,
            ]);
        $onWayResponse->assertOk();

        // Step 4: Admin updates to arrived
        $arrivedResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/{$uuid}/status", [
                'status' => 'arrived',
                'vet_latitude' => 40.7128,
                'vet_longitude' => -74.006,
            ]);
        $arrivedResponse->assertOk();

        // Step 5: Admin updates to sos_in_progress
        $inProgressResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/{$uuid}/status", [
                'status' => 'sos_in_progress',
            ]);
        $inProgressResponse->assertOk();

        // Step 6: Admin completes
        $completeResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/{$uuid}/status", [
                'status' => 'sos_completed',
                'resolution_notes' => 'Pet stabilized. Medication prescribed.',
                'emergency_charge' => 2000,
                'distance_travelled_km' => 5.5,
            ]);
        $completeResponse->assertOk();

        // Verify final state
        $this->assertDatabaseHas('sos_requests', [
            'uuid' => $uuid,
            'status' => 'sos_completed',
        ]);
    }

    // ─── 6. Rate Limiting ────────────────────────────────────────────

    public function test_sos_rate_limit_5_per_hour(): void
    {
        // Create 5 SOS and cancel each
        for ($i = 0; $i < 5; $i++) {
            $sos = SosRequest::factory()->forUser($this->user)->pending()->create();
            $sos->update(['status' => 'cancelled']);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'description' => 'Sixth SOS attempt within one hour should be blocked',
                'emergency_type' => 'injury',
            ]);

        $response->assertStatus(429);
    }

    // ─── 7. Assigned Vet Can Update SOS ──────────────────────────────

    public function test_assigned_vet_can_update_sos_status(): void
    {
        // Create SOS with vet already assigned
        $sos = SosRequest::factory()->forUser($this->user)->create([
            'status' => 'sos_accepted',
            'assigned_vet_id' => $this->vetProfile->id,
            'acknowledged_at' => now(),
        ]);

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'vet_on_the_way',
                'vet_latitude' => 40.7100,
                'vet_longitude' => -74.010,
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['sos' => ['status' => 'vet_on_the_way']]]);
    }

    public function test_assigned_vet_full_lifecycle(): void
    {
        // Create pre-assigned SOS
        $sos = SosRequest::factory()->forUser($this->user)->create([
            'status' => 'sos_accepted',
            'assigned_vet_id' => $this->vetProfile->id,
            'acknowledged_at' => now(),
        ]);

        // Vet on the way
        $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'vet_on_the_way',
                'vet_latitude' => 40.7100,
                'vet_longitude' => -74.010,
            ])->assertOk();

        // Vet arrived
        $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'arrived',
                'vet_latitude' => 40.7128,
                'vet_longitude' => -74.006,
            ])->assertOk();

        // Treatment in progress
        $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'sos_in_progress',
            ])->assertOk();

        // Complete
        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'sos_completed',
                'resolution_notes' => 'Treatment completed on-site.',
                'emergency_charge' => 1500,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('sos_requests', [
            'id' => $sos->id,
            'status' => 'sos_completed',
        ]);
    }

    // ─── 8. Invalid Transitions ──────────────────────────────────────

    public function test_invalid_status_transition_rejected(): void
    {
        // pending → completed is not allowed
        $sos = SosRequest::factory()->forUser($this->user)->pending()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$sos->uuid}/status", [
                'status' => 'sos_completed',
            ]);

        $response->assertStatus(422);
    }
}
