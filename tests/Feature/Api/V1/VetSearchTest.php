<?php

namespace Tests\Feature\Api\V1;

use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VetSearchTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/vets';

    // ═══════════════════════════════════════════════════════════════════════
    // VET SEARCH
    // ═══════════════════════════════════════════════════════════════════════

    public function test_search_vets_requires_coordinates(): void
    {
        $response = $this->getJson("{$this->prefix}");

        $response->assertStatus(422);
    }

    public function test_search_vets_with_valid_coordinates(): void
    {
        VetProfile::factory()->create([
            'latitude' => 19.076090,
            'longitude' => 72.877426,
            'is_verified' => true,
        ]);

        $response = $this->getJson("{$this->prefix}?lat=19.076&lng=72.877");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'vets',
                    'search_params' => ['latitude', 'longitude', 'radius_km'],
                ],
            ]);
    }

    public function test_search_vets_invalid_latitude(): void
    {
        $response = $this->getJson("{$this->prefix}?lat=200&lng=72.877");

        $response->assertStatus(422);
    }

    public function test_search_vets_invalid_longitude(): void
    {
        $response = $this->getJson("{$this->prefix}?lat=19.076&lng=999");

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // VET DETAIL
    // ═══════════════════════════════════════════════════════════════════════

    public function test_show_vet_by_uuid(): void
    {
        $vet = VetProfile::factory()->create(['is_verified' => true]);

        $response = $this->getJson("{$this->prefix}/{$vet->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.vet.uuid', $vet->uuid);
    }

    public function test_show_nonexistent_vet(): void
    {
        $response = $this->getJson("{$this->prefix}/nonexistent-uuid");

        $response->assertStatus(404);
    }
}
