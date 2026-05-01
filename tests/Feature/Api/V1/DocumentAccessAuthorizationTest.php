<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pet;
use App\Models\PetDocument;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_vet_document_for_review(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vetProfile = VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'license_document_url' => 'vet-documents/test-license.pdf',
        ]);

        Storage::disk('local')->put('vet-documents/test-license.pdf', 'license-content');

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/admin/vets/{$vetProfile->uuid}/documents/license");

        $response->assertOk();
    }

    public function test_non_admin_cannot_view_admin_vet_document_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $vetProfile = VetProfile::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/admin/vets/{$vetProfile->uuid}/documents/license");

        $response->assertStatus(403);
    }

    public function test_owner_can_list_pet_documents(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create(['role' => 'user']);
        $pet = Pet::factory()->forUser($owner)->create();

        $document = PetDocument::create([
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'title' => 'Vaccination record',
            'document_type' => 'vaccination_record',
            'file_path' => 'pets/' . $pet->id . '/documents/vax.pdf',
            'file_name' => 'vax.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('private')->put($document->file_path, 'pdf-content');

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/pets/{$pet->id}/documents");

        $response->assertOk();
    }

    public function test_related_vet_cannot_list_pet_documents(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create(['role' => 'user']);
        $pet = Pet::factory()->forUser($owner)->create();

        $vetUser = User::factory()->create(['role' => 'vet']);
        $vetProfile = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        // Create a relationship that would normally allow pet view via policy.
        $owner->appointments()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'vet_profile_id' => $vetProfile->id,
            'pet_id' => $pet->id,
            'status' => 'completed',
            'appointment_type' => 'clinic_visit',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 30,
            'reason' => 'Past appointment',
            'fee_amount' => 500,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($vetUser, 'sanctum')
            ->getJson("/api/v1/pets/{$pet->id}/documents");

        $response->assertStatus(403);
    }

    public function test_admin_can_download_pet_document(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $pet = Pet::factory()->forUser($owner)->create();

        $document = PetDocument::create([
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'title' => 'Lab result',
            'document_type' => 'lab_result',
            'file_path' => 'pets/' . $pet->id . '/documents/lab.pdf',
            'file_name' => 'lab.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('private')->put($document->file_path, 'pdf-content');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/pets/{$pet->id}/documents/{$document->uuid}/download");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['download_url', 'expires_at']]);
    }
}
