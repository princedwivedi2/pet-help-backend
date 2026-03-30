<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VetSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeApprovedVet(array $overrides = []): VetProfile
    {
        $vetUser = User::factory()->create(['role' => 'vet']);

        return VetProfile::factory()
            ->verified()
            ->state(array_merge([
                'user_id' => $vetUser->id,
                'is_active' => true,
                'is_emergency_available' => true,
                'latitude' => 19.0700,
                'longitude' => 72.8700,
            ], $overrides))
            ->create();
    }

    public function test_nearby_vet_shows_in_nearby_bucket(): void
    {
        $vet = $this->makeApprovedVet(['latitude' => 19.0700, 'longitude' => 72.8700]);

        $response = $this->getJson('/api/v1/vets?lat=19.0700&lng=72.8700&radius_km=5');

        $response->assertOk();

        $uuids = collect($response->json('data.nearby_vets'))->pluck('uuid');
        $this->assertTrue($uuids->contains($vet->uuid), 'Expected vet to appear in nearby_vets');
    }

    public function test_vet_outside_radius_not_in_nearby_bucket(): void
    {
        $this->makeApprovedVet(['latitude' => 19.0700, 'longitude' => 72.8700]);
        $farVet = $this->makeApprovedVet(['latitude' => 19.2183, 'longitude' => 72.8700]);

        $response = $this->getJson('/api/v1/vets?lat=19.0700&lng=72.8700&radius_km=10');

        $response->assertOk();

        $nearbyUuids = collect($response->json('data.nearby_vets'))->pluck('uuid');
        $this->assertFalse($nearbyUuids->contains($farVet->uuid), 'Far vet should not appear in nearby_vets');
    }

    public function test_emergency_filter_only_returns_emergency_vets(): void
    {
        $emergencyVet = $this->makeApprovedVet(['is_emergency_available' => true]);
        $nonEmergencyVet = $this->makeApprovedVet(['is_emergency_available' => false]);

        $response = $this->getJson('/api/v1/vets?lat=19.0700&lng=72.8700&emergency_only=1');

        $response->assertOk();

        $uuids = collect($response->json('data.vets'))->pluck('uuid');
        $this->assertTrue($uuids->contains($emergencyVet->uuid), 'Emergency vet should be included');
        $this->assertFalse($uuids->contains($nonEmergencyVet->uuid), 'Non-emergency vet should be excluded');
    }

    public function test_unapproved_vet_is_hidden(): void
    {
        $approved = $this->makeApprovedVet();
        $pendingVet = VetProfile::factory()->state([
            'user_id' => User::factory()->create(['role' => 'vet'])->id,
            'vet_status' => 'pending',
            'is_active' => true,
            'latitude' => 19.0700,
            'longitude' => 72.8700,
        ])->create();

        $response = $this->getJson('/api/v1/vets?lat=19.0700&lng=72.8700&radius_km=5');

        $response->assertOk();

        $uuids = collect($response->json('data.vets'))->pluck('uuid');
        $this->assertTrue($uuids->contains($approved->uuid));
        $this->assertFalse($uuids->contains($pendingVet->uuid), 'Pending vet should be hidden');
    }
}
