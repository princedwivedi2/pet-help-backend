<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VetOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private string $registerUrl = '/api/v1/vet/register';
    private string $profileUrl = '/api/v1/vet/profile';

    private function validVetData(): array
    {
        return [
            'full_name' => 'Dr. Sarah Johnson',
            'email' => 'dr.sarah@vetclinic.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'phone_number' => '+919876543210',
            'phone' => '+919876543210',
            'profile_photo' => 'https://example.com/profile.jpg',
            'clinic_name' => 'Happy Paws Clinic',
            'clinic_address' => '123 Vet Lane, Mumbai, Maharashtra, 400001',
            'city' => 'Mumbai',
            'latitude' => 19.076090,
            'longitude' => 72.877426,
            'qualifications' => 'BVSc, MVSc Veterinary Surgery',
            'qualification' => 'BVSc, MVSc Veterinary Surgery',
            'license_number' => 'VET-MH-2024-001',
            'years_of_experience' => 8,
            'specialization' => 'Small Animal Medicine',
            'consultation_fee' => 700,
            'accepted_species' => ['dog', 'cat', 'bird'],
            'services_offered' => ['consultation', 'vaccination', 'surgery'],
            'working_hours' => ['mon' => ['09:00-17:00']],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REGISTRATION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_vet_register_success(): void
    {
        $response = $this->postJson($this->registerUrl, $this->validVetData());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'vet_profile' => ['uuid', 'clinic_name'],
                ],
            ])
            ->assertJsonPath('data.user.role', 'vet');

        $this->assertDatabaseHas('users', [
            'email' => 'dr.sarah@vetclinic.com',
            'role' => 'vet',
        ]);

        $this->assertDatabaseHas('vet_profiles', [
            'license_number' => 'VET-MH-2024-001',
            'vet_status' => 'pending',
        ]);
    }

    public function test_vet_register_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dr.sarah@vetclinic.com']);

        $response = $this->postJson($this->registerUrl, $this->validVetData());

        $response->assertStatus(422);
    }

    public function test_vet_register_duplicate_license(): void
    {
        $this->postJson($this->registerUrl, $this->validVetData());

        $data = $this->validVetData();
        $data['email'] = 'other@vet.com';
        $response = $this->postJson($this->registerUrl, $data);

        $response->assertStatus(422);
    }

    public function test_vet_register_weak_password(): void
    {
        $data = $this->validVetData();
        $data['password'] = 'weak';
        $data['password_confirmation'] = 'weak';

        $response = $this->postJson($this->registerUrl, $data);

        $response->assertStatus(422);
    }

    public function test_vet_register_missing_required_fields(): void
    {
        $response = $this->postJson($this->registerUrl, []);

        $response->assertStatus(422);
    }

    public function test_vet_register_invalid_phone(): void
    {
        $data = $this->validVetData();
        $data['phone_number'] = 'invalid';

        $response = $this->postJson($this->registerUrl, $data);

        $response->assertStatus(422);
    }

    public function test_vet_register_empty_species(): void
    {
        $data = $this->validVetData();
        $data['accepted_species'] = [];

        $response = $this->postJson($this->registerUrl, $data);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // VET PROFILE
    // ═══════════════════════════════════════════════════════════════════════

    public function test_vet_can_view_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->profileUrl);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_non_vet_cannot_view_vet_profile(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson($this->profileUrl);

        $response->assertStatus(403);
    }

    public function test_vet_profile_requires_auth(): void
    {
        $response = $this->getJson($this->profileUrl);

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: VET VERIFICATION
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_unverified_vets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        VetProfile::factory()->count(2)->create(['vet_status' => 'pending']);
        VetProfile::factory()->create(['vet_status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/vets/unverified');

        $response->assertOk()
            ->assertJsonCount(2, 'data.vets');
    }

    public function test_admin_approve_vet(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('vet-documents/license.pdf', 'license');
        Storage::disk('public')->put('vet-documents/degree.pdf', 'degree');
        Storage::disk('public')->put('vet-documents/id.pdf', 'id');

        $admin = User::factory()->create(['role' => 'admin']);
        $vet = VetProfile::factory()->create([
            'vet_status' => 'pending',
            'profile_photo' => 'https://example.com/profile.jpg',
            'license_number' => 'VERIFY-VET-001',
            'qualifications' => 'BVSc, MVSc',
            'specialization' => 'General Practice',
            'years_of_experience' => 5,
            'consultation_fee' => 700,
            'home_visit_fee' => 1000,
            'working_hours' => ['mon' => ['09:00-17:00']],
            'license_document_url' => 'vet-documents/license.pdf',
            'degree_certificate_url' => 'vet-documents/degree.pdf',
            'government_id_url' => 'vet-documents/id.pdf',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vets/{$vet->uuid}/verify", [
                'action' => 'approve',
            ]);

        $response->assertOk();
        $this->assertSame('approved', $vet->fresh()->vet_status);
    }

    public function test_admin_reject_vet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vet = VetProfile::factory()->create(['vet_status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vets/{$vet->uuid}/verify", [
                'action' => 'reject',
                'reason' => 'Invalid license number provided',
            ]);

        $response->assertOk();
    }

    public function test_admin_reject_vet_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vet = VetProfile::factory()->create(['vet_status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vets/{$vet->uuid}/verify", [
                'action' => 'reject',
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_vet_verification_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vet = VetProfile::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/vets/{$vet->uuid}/history");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['vet_profile', 'history']]);
    }

    public function test_vet_verification_forbidden_for_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $vet = VetProfile::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/admin/vets/{$vet->uuid}/verify", [
                'action' => 'approve',
            ]);

        $response->assertStatus(403);
    }
}
