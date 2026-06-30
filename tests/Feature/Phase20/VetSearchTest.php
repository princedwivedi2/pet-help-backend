<?php

namespace Tests\Feature\Phase20;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-04 RED test: GET /vets?search=term filters by clinic_name and user.name.
 *
 * D-04: search must be case-insensitive LIKE across users.name AND vet_profiles.clinic_name.
 *
 * NOTE: This class is in namespace Tests\Feature\Phase20 — distinct from
 * Tests\Feature\Api\V1\VetSearchTest (geo/coordinate tests). No collision.
 *
 * RED wave: search param is currently ignored; all vets are returned regardless.
 * Plan 20-02/03 wires the search param into VetSearchService::discoverApprovedVets().
 */
class VetSearchTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/vets';

    /**
     * RED: Searching by clinic_name must return only matching vets.
     * Currently all vets are returned (search param ignored).
     */
    public function test_search_matches_clinic_name(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $matchingVet = VetProfile::factory()->verified()->create([
            'user_id'     => $vetUser->id,
            'clinic_name' => 'Happy Paws Clinic',
        ]);

        // A second vet that should NOT appear in search results
        $otherVetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create([
            'user_id'     => $otherVetUser->id,
            'clinic_name' => 'Riverside Animal Hospital',
        ]);

        $response = $this->getJson("{$this->prefix}?search=Happy");

        $response->assertOk();

        // Collect all returned vet uuids across all buckets
        $data = $response->json('data');
        $returnedUuids = collect()
            ->merge($data['all_vets'] ?? [])
            ->merge($data['vets'] ?? [])
            ->pluck('uuid')
            ->unique()
            ->values()
            ->all();

        $this->assertContains($matchingVet->uuid, $returnedUuids,
            'Vet matching clinic_name should be present in search results.');
    }

    /**
     * RED: Searching by vet's user.name must return only matching vets.
     * Currently search param is not wired to users.name lookup.
     */
    public function test_search_matches_user_name(): void
    {
        $vetUser = User::factory()->create([
            'role' => 'vet',
            'name' => 'Dr Strange',
        ]);
        $matchingVet = VetProfile::factory()->verified()->create([
            'user_id' => $vetUser->id,
        ]);

        $otherVetUser = User::factory()->create([
            'role' => 'vet',
            'name' => 'John Normal',
        ]);
        VetProfile::factory()->verified()->create([
            'user_id' => $otherVetUser->id,
        ]);

        $response = $this->getJson("{$this->prefix}?search=Strange");

        $response->assertOk();

        $data = $response->json('data');
        $returnedUuids = collect()
            ->merge($data['all_vets'] ?? [])
            ->merge($data['vets'] ?? [])
            ->pluck('uuid')
            ->unique()
            ->values()
            ->all();

        $this->assertContains($matchingVet->uuid, $returnedUuids,
            'Vet matching user.name should be present in search results.');
    }

    /**
     * RED: Non-matching vet must be absent when a specific search term is applied.
     */
    public function test_search_excludes_nonmatching(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create([
            'user_id'     => $vetUser->id,
            'clinic_name' => 'Happy Paws Clinic',
        ]);

        $otherVetUser = User::factory()->create(['role' => 'vet', 'name' => 'John Regular']);
        $nonMatchingVet = VetProfile::factory()->verified()->create([
            'user_id'     => $otherVetUser->id,
            'clinic_name' => 'Riverside Animal Hospital',
        ]);

        $response = $this->getJson("{$this->prefix}?search=Happy");

        $response->assertOk();

        $data = $response->json('data');
        $returnedUuids = collect()
            ->merge($data['all_vets'] ?? [])
            ->merge($data['vets'] ?? [])
            ->pluck('uuid')
            ->unique()
            ->values()
            ->all();

        $this->assertNotContains($nonMatchingVet->uuid, $returnedUuids,
            'Non-matching vet must be excluded from search results.');
    }

    /**
     * Backward compat: no search param → all approved vets returned.
     * This test should stay GREEN before and after the fix.
     */
    public function test_no_search_returns_all(): void
    {
        $vet1User = User::factory()->create(['role' => 'vet']);
        $vet1 = VetProfile::factory()->verified()->create(['user_id' => $vet1User->id]);

        $vet2User = User::factory()->create(['role' => 'vet']);
        $vet2 = VetProfile::factory()->verified()->create(['user_id' => $vet2User->id]);

        $response = $this->getJson($this->prefix); // no search param

        $response->assertOk();

        $data = $response->json('data');
        $returnedUuids = collect()
            ->merge($data['all_vets'] ?? [])
            ->merge($data['vets'] ?? [])
            ->pluck('uuid')
            ->unique()
            ->values()
            ->all();

        $this->assertContains($vet1->uuid, $returnedUuids, 'Vet 1 should be present without search filter.');
        $this->assertContains($vet2->uuid, $returnedUuids, 'Vet 2 should be present without search filter.');
    }
}
