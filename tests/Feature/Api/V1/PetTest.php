<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pet;
use App\Models\User;
use App\Services\PetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/pets';

    private function authUser(): User
    {
        return User::factory()->create();
    }

    // ─── List ────────────────────────────────────────────────────────

    public function test_list_pets_authenticated(): void
    {
        $user = $this->authUser();
        Pet::factory()->count(3)->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data.pets');
    }

    public function test_list_pets_only_own(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        Pet::factory()->count(2)->forUser($user)->create();
        Pet::factory()->count(3)->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJsonCount(2, 'data.pets');
    }

    public function test_list_pets_unauthenticated(): void
    {
        $this->getJson($this->prefix)->assertStatus(401);
    }

    // ─── Create ──────────────────────────────────────────────────────

    public function test_create_pet_success(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, [
                'name' => 'Buddy',
                'species' => 'dog',
                'breed' => 'Labrador',
                'weight_kg' => 25.5,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['pet' => ['name' => 'Buddy', 'species' => 'dog']],
            ]);

        $this->assertDatabaseHas('pets', [
            'user_id' => $user->id,
            'name' => 'Buddy',
        ]);
    }

    public function test_create_pet_minimal_fields(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, [
                'name' => 'Min',
                'species' => 'cat',
            ]);

        $response->assertStatus(201);
    }

    public function test_create_pet_invalid_species(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, [
                'name' => 'Alien',
                'species' => 'dinosaur',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_pet_max_limit(): void
    {
        $user = $this->authUser();
        Pet::factory()->count(PetService::MAX_PETS_PER_USER)->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson($this->prefix, [
                'name' => 'OneMore',
                'species' => 'dog',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function test_show_own_pet(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/{$pet->id}");

        $response->assertOk()
            ->assertJson(['data' => ['pet' => ['id' => $pet->id]]]);
    }

    public function test_show_other_users_pet(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/{$pet->id}");

        $response->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function test_update_pet_success(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$pet->id}", [
                'name' => 'Updated',
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['pet' => ['name' => 'Updated']]]);
    }

    public function test_update_other_users_pet(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/{$pet->id}", ['name' => 'Hacked']);

        $response->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function test_delete_pet_success(): void
    {
        $user = $this->authUser();
        $pet = Pet::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/{$pet->id}");

        $response->assertOk();
        $this->assertSoftDeleted('pets', ['id' => $pet->id]);
    }

    public function test_delete_other_users_pet(): void
    {
        $user = $this->authUser();
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/{$pet->id}");

        $response->assertStatus(404);
    }
}
