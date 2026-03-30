<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vet Onboarding & Profile QA Test
 * Covers: Apply, Register, Profile management, Consultation types, Availabilities
 */
class VetOnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    // ─── 1. Vet Registration ─────────────────────────────────────────

    public function test_vet_can_apply(): void
    {
        $response = $this->postJson('/api/v1/vet/apply', [
            'full_name' => 'Dr. Test Vet',
            'email' => 'vet@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'clinic_name' => 'Test Veterinary Clinic',
            'phone' => '9876543210',
            'profile_photo' => 'https://example.com/photo.jpg',
            'license_number' => 'VET123456',
            'clinic_address' => '123 Vet Street',
            'city' => 'Mumbai',
            'state' => 'MH',
            'postal_code' => '400001',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'qualifications' => 'BVSc',
            'years_of_experience' => 5,
            'specialization' => 'General Practice',
            'consultation_fee' => 500,
            'accepted_species' => ['dog', 'cat'],
            'services_offered' => ['consultation'],
            'working_hours' => ['mon' => ['09:00-17:00']],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email' => 'vet@example.com',
            'role' => 'vet',
        ]);

        $this->assertDatabaseHas('vet_profiles', [
            'clinic_name' => 'Test Veterinary Clinic',
        ]);
    }

    // ─── 2. Vet Profile Management ──────────────────────────────────

    public function test_vet_can_view_profile(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->getJson('/api/v1/vet/profile');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_vet_can_update_profile(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->putJson('/api/v1/vet/profile', [
                'clinic_name' => 'Updated Clinic',
                'vet_name' => 'Dr. Updated Vet',
                'phone' => '9876543210',
                'profile_photo' => 'https://example.com/new-photo.jpg',
                'license_number' => 'UPD-VET-12345',
                'qualification' => 'BVSc, MVSc',
                'clinic_address' => 'Updated Address, Mumbai',
                'latitude' => 19.076,
                'longitude' => 72.877,
                'consultation_fee' => 600,
                'home_visit_fee' => 1000,
                'max_home_visit_km' => 20,
                'services' => ['general', 'vaccination', 'dental', 'surgery'],
                'accepted_species' => ['dog', 'cat', 'bird'],
                'working_hours' => ['mon' => ['09:00-17:00']],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('vet_profiles', [
            'id' => $vet->id,
            'consultation_fee' => 600,
            'home_visit_fee' => 1000,
        ]);
    }

    public function test_non_vet_cannot_access_vet_profile(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/vet/profile');

        $response->assertStatus(403);
    }

    // ─── 3. Availabilities ───────────────────────────────────────────

    public function test_vet_can_list_availabilities(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->getJson('/api/v1/vet/availabilities');

        $response->assertOk();
    }

    public function test_vet_can_create_availability(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->postJson('/api/v1/vet/availabilities', [
                'day_of_week' => 1,
                'open_time' => '09:00',
                'close_time' => '17:00',
                'is_emergency_hours' => false,
            ]);

        $response->assertStatus(201);
    }

    // ─── 4. Vet Status Update ────────────────────────────────────────

    public function test_vet_can_update_availability_status(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->putJson('/api/v1/vet/status', [
                'availability_status' => 'available',
            ]);

        $response->assertOk();
    }

    // ─── 5. Public Vet Listing ───────────────────────────────────────

    public function test_public_can_list_vets(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->getJson('/api/v1/vets');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_public_can_view_single_vet(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->getJson("/api/v1/vets/{$vet->uuid}");

        $response->assertOk();
    }
}
