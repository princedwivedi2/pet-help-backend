<?php

namespace Tests\Feature\Phase20;

use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-03 RED test: List endpoints return data.pagination envelope.
 *
 * D-06/D-07/D-08:
 *   - GET /pets   must include data.pagination (current_page, last_page, per_page, total)
 *   - GET /vets   must include data.pagination
 *   - data.pagination shape identical to appointments pagination
 *   - Default per_page=15, max per_page=50
 *
 * RED wave: GET /pets returns data.pets as a flat array (no pagination key).
 * GET /vets has no pagination envelope in any bucket.
 * Plan 20-03 adds pagination to PetController::index() and VetController::index().
 */
class PaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * RED: GET /pets must return data.pagination with the four required keys.
     * Currently data.pagination is absent — PetController returns a flat Collection.
     */
    public function test_pets_returns_pagination_envelope(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Pet::factory()->count(3)->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pets',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);

        // Verify the pets array is actually an array
        $this->assertIsArray($response->json('data.pets'),
            'data.pets must be an array.');
    }

    /**
     * RED: GET /vets must return data.pagination with the four required keys.
     * Currently the vet controller returns buckets with no pagination object.
     */
    public function test_vets_returns_pagination_envelope(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->getJson('/api/v1/vets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    /**
     * RED: per_page must be capped at 50.
     * GET /api/v1/pets?per_page=999 → data.pagination.per_page == 50.
     * Currently PetController ignores per_page entirely (returns flat collection).
     */
    public function test_pets_per_page_capped_at_50(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Pet::factory()->count(5)->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pets?per_page=999');

        $response->assertOk();

        $perPage = $response->json('data.pagination.per_page');
        $this->assertNotNull($perPage, 'data.pagination.per_page must be present.');
        $this->assertLessThanOrEqual(50, (int) $perPage,
            'per_page must be capped at 50 — client passed 999 but must receive ≤ 50.');
    }
}
