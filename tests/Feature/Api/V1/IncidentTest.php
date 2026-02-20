<?php

namespace Tests\Feature\Api\V1;

use App\Models\IncidentLog;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/incidents';

    private function authUser(): User
    {
        return User::factory()->create();
    }

    // ─── List ────────────────────────────────────────────────────────

    public function test_list_incidents_authenticated(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();
        IncidentLog::factory()->count(5)->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'incidents',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_list_incidents_only_own(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($user)->create();
        $otherPet = Pet::factory()->forUser($other)->create();

        IncidentLog::factory()->count(3)->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
        ]);
        IncidentLog::factory()->count(2)->create([
            'user_id' => $other->id,
            'pet_id' => $otherPet->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 3);
    }

    public function test_list_incidents_filter_by_pet(): void
    {
        $user = $this->authUser();
        $pet1 = Pet::factory()->forUser($user)->create();
        $pet2 = Pet::factory()->forUser($user)->create();

        IncidentLog::factory()->count(2)->create([
            'user_id' => $user->id,
            'pet_id' => $pet1->id,
        ]);
        IncidentLog::factory()->count(3)->create([
            'user_id' => $user->id,
            'pet_id' => $pet2->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}?pet_id={$pet1->id}");

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_list_incidents_filter_by_status(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();

        IncidentLog::factory()->count(2)->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
            'status' => 'resolved',
        ]);
        IncidentLog::factory()->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}?status=resolved");

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_list_incidents_filter_by_other_users_pet(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $otherPet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}?pet_id={$otherPet->id}");

        $response->assertStatus(422);
    }

    public function test_list_incidents_unauthenticated(): void
    {
        $this->getJson($this->prefix)->assertStatus(401);
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function test_show_own_incident(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();
        $incident = IncidentLog::factory()->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/{$incident->uuid}");

        $response->assertOk()
            ->assertJson(['data' => ['incident' => ['uuid' => $incident->uuid]]]);
    }

    public function test_show_other_users_incident(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $otherPet = Pet::factory()->forUser($other)->create();
        $incident = IncidentLog::factory()->create([
            'user_id' => $other->id,
            'pet_id' => $otherPet->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/{$incident->uuid}");

        $response->assertStatus(404);
    }

    public function test_show_nonexistent_incident(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/non-existent-uuid");

        $response->assertStatus(404);
    }
}
