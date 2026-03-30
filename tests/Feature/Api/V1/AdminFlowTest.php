<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin Operations QA Test
 * Covers: Dashboard stats, Vet management, User listing, SOS & Appointment views
 */
class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin';
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ─── 1. Dashboard Stats ──────────────────────────────────────────

    public function test_admin_can_get_stats(): void
    {
        // Seed some data
        User::factory()->count(3)->create();
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/stats");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_non_admin_cannot_access_stats(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/stats");

        $response->assertStatus(403);
    }

    // ─── 2. User Management ─────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/users");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/users/{$user->id}/role", [
                'role' => 'vet',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'vet',
        ]);
    }

    // ─── 3. Vet Management ──────────────────────────────────────────

    public function test_admin_can_list_vets_by_status(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->create(['user_id' => $vetUser->id, 'vet_status' => 'pending']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/vets?status=pending");

        $response->assertOk();
    }

    public function test_admin_can_approve_vet(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('vet-documents/license.pdf', 'license');
        Storage::disk('public')->put('vet-documents/degree.pdf', 'degree');
        Storage::disk('public')->put('vet-documents/id.pdf', 'id');

        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'pending',
            'profile_photo' => 'https://example.com/profile.jpg',
            'license_number' => 'APPROVE-VET-001',
            'qualifications' => 'BVSc, MVSc',
            'specialization' => 'General Practice',
            'years_of_experience' => 5,
            'consultation_fee' => 600,
            'home_visit_fee' => 900,
            'working_hours' => ['mon' => ['09:00-17:00']],
            'license_document_url' => 'vet-documents/license.pdf',
            'degree_certificate_url' => 'vet-documents/degree.pdf',
            'government_id_url' => 'vet-documents/id.pdf',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/vets/{$vet->uuid}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('vet_profiles', [
            'id' => $vet->id,
            'vet_status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_vet(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/vets/{$vet->uuid}/reject", [
                'reason' => 'Incomplete documentation provided',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('vet_profiles', [
            'id' => $vet->id,
            'vet_status' => 'rejected',
        ]);
    }

    public function test_admin_can_suspend_vet(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create([
            'user_id' => $vetUser->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("{$this->prefix}/vets/{$vet->uuid}/suspend", [
                'reason' => 'Multiple complaints received',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('vet_profiles', [
            'id' => $vet->id,
            'vet_status' => 'suspended',
        ]);
    }

    public function test_admin_can_view_unverified_vets(): void
    {
        $vetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/vets/unverified");

        $response->assertOk();
    }

    // ─── 4. View SOS ────────────────────────────────────────────────

    public function test_admin_can_view_all_sos(): void
    {
        $user = User::factory()->create();
        SosRequest::factory()->forUser($user)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/sos");

        $response->assertOk();
    }

    // ─── 5. View Appointments ────────────────────────────────────────

    public function test_admin_can_view_all_appointments(): void
    {
        $user = User::factory()->create();
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $pet = Pet::factory()->forUser($user)->create();
        Appointment::factory()->forUser($user)->forVet($vet)->create(['pet_id' => $pet->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/appointments");

        $response->assertOk();
    }

    // ─── 6. Revenue ──────────────────────────────────────────────────

    public function test_admin_can_view_revenue(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/revenue");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── 7. Metrics ──────────────────────────────────────────────────

    public function test_admin_can_view_metrics(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/metrics");

        $response->assertOk();
    }

    // ─── 8. Audit Logs ──────────────────────────────────────────────

    public function test_admin_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/audit-logs");

        $response->assertOk();
    }

    // ─── 9. Incidents ────────────────────────────────────────────────

    public function test_admin_can_view_incidents(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/incidents");

        $response->assertOk();
    }

    // ─── 10. Pets (admin view) ───────────────────────────────────────

    public function test_admin_can_view_all_pets(): void
    {
        $user = User::factory()->create();
        Pet::factory()->forUser($user)->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("{$this->prefix}/pets");

        $response->assertOk();
    }
}
