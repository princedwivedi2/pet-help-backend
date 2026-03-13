<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pet CRUD QA Test
 * Covers: Create, Read, Update, Delete pets
 */
class PetCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/pets';
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    // ─── 1. Create ──────────────────────────────────────────────────

    public function test_user_can_create_pet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'name' => 'Buddy',
                'species' => 'dog',
                'breed' => 'Golden Retriever',
                'birth_date' => '2020-05-15',
                'weight_kg' => 28.5,
                'medical_notes' => 'Allergic to chicken',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.pet.name', 'Buddy');

        $this->assertDatabaseHas('pets', [
            'user_id' => $this->user->id,
            'name' => 'Buddy',
            'species' => 'dog',
        ]);
    }

    public function test_create_pet_requires_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->prefix, [
                'species' => 'dog',
            ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_create_pet(): void
    {
        $this->postJson($this->prefix, [
            'name' => 'Buddy',
            'species' => 'dog',
        ])->assertStatus(401);
    }

    // ─── 2. Read / List ─────────────────────────────────────────────

    public function test_user_can_list_own_pets(): void
    {
        Pet::factory()->forUser($this->user)->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_user_can_view_own_pet(): void
    {
        $pet = Pet::factory()->forUser($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/{$pet->id}");

        $response->assertOk()
            ->assertJsonPath('data.pet.id', $pet->id);
    }

    public function test_user_cannot_view_other_users_pet(): void
    {
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}/{$pet->id}");

        $response->assertStatus(404); // API hides non-owned resources
    }

    // ─── 3. Update ──────────────────────────────────────────────────

    public function test_user_can_update_own_pet(): void
    {
        $pet = Pet::factory()->forUser($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$pet->id}", [
                'name' => 'Updated Pet Name',
                'weight_kg' => 30.0,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'name' => 'Updated Pet Name',
        ]);
    }

    public function test_user_cannot_update_other_users_pet(): void
    {
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->prefix}/{$pet->id}", [
                'name' => 'Not my pet',
            ]);

        $response->assertStatus(404); // API hides non-owned resources
    }

    // ─── 4. Delete ──────────────────────────────────────────────────

    public function test_user_can_delete_own_pet(): void
    {
        $pet = Pet::factory()->forUser($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("{$this->prefix}/{$pet->id}");

        $response->assertOk();
    }

    public function test_user_cannot_delete_other_users_pet(): void
    {
        $other = User::factory()->create();
        $pet = Pet::factory()->forUser($other)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("{$this->prefix}/{$pet->id}");

        $response->assertStatus(404); // API hides non-owned resources
    }
}
