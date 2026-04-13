<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pet;
use App\Models\PetMedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetMedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Pet $pet;
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
        $this->pet = Pet::factory()->forUser($this->user)->create();
        $this->baseUrl = "/api/v1/pets/{$this->pet->id}/medical-records";
    }

    // ─── Authentication ─────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_medical_records(): void
    {
        $this->getJson($this->baseUrl)->assertStatus(401);
        $this->postJson($this->baseUrl, [])->assertStatus(401);
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function test_user_can_list_medical_records_for_own_pet(): void
    {
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data.records')
            ->assertJsonStructure([
                'data' => [
                    'pet_id',
                    'records',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_user_cannot_list_records_for_another_users_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/pets/{$otherPet->id}/medical-records")
            ->assertStatus(404);
    }

    public function test_index_filters_by_record_type(): void
    {
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->diagnosis()->count(2)->create();
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->medicine()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->baseUrl}?type=diagnosis");

        $response->assertOk()
            ->assertJsonCount(2, 'data.records');
    }

    public function test_index_filters_by_date_range(): void
    {
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->create([
            'recorded_at' => '2024-01-15',
        ]);
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->create([
            'recorded_at' => '2024-06-01',
        ]);
        PetMedicalRecord::factory()->forPet($this->pet)->forUser($this->user)->create([
            'recorded_at' => '2025-01-01',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->baseUrl}?from=2024-01-01&to=2024-12-31");

        $response->assertOk()
            ->assertJsonCount(2, 'data.records');
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function test_user_can_create_general_record(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'general',
                'title' => 'Annual check-up',
                'description' => 'Healthy weight, no abnormalities.',
                'recorded_at' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.record.title', 'Annual check-up')
            ->assertJsonPath('data.record.record_type', 'general');

        $this->assertDatabaseHas('pet_medical_records', [
            'pet_id' => $this->pet->id,
            'record_type' => 'general',
            'title' => 'Annual check-up',
        ]);
    }

    public function test_user_can_create_medicine_record_with_all_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'medicine',
                'title' => 'Antibiotic course',
                'description' => 'Prescribed after dental surgery',
                'medicine_name' => 'Amoxicillin',
                'medicine_dosage' => '25mg',
                'medicine_frequency' => 'Twice daily',
                'medicine_duration' => '7 days',
                'recorded_at' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.record.medicine_name', 'Amoxicillin');

        $this->assertDatabaseHas('pet_medical_records', [
            'pet_id' => $this->pet->id,
            'record_type' => 'medicine',
            'medicine_name' => 'Amoxicillin',
        ]);
    }

    public function test_medicine_record_requires_medicine_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'medicine',
                'title' => 'Missing medicine name',
                'recorded_at' => now()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_create_record_requires_title(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'general',
                'recorded_at' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_create_record_requires_valid_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'invalid_type',
                'title' => 'Something',
                'recorded_at' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_create_record_rejects_future_recorded_at(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'general',
                'title' => 'Future record',
                'recorded_at' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_create_record_for_another_users_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/pets/{$otherPet->id}/medical-records", [
                'record_type' => 'general',
                'title' => 'Unauthorized',
                'recorded_at' => now()->toDateString(),
            ])
            ->assertStatus(404);
    }

    public function test_user_can_create_vaccination_record(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->baseUrl, [
                'record_type' => 'vaccination',
                'title' => 'Rabies vaccine',
                'description' => 'Annual rabies vaccination administered.',
                'recorded_at' => now()->subDays(30)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.record.record_type', 'vaccination');
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function test_user_can_show_own_medical_record(): void
    {
        $record = PetMedicalRecord::factory()
            ->forPet($this->pet)
            ->forUser($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->baseUrl}/{$record->uuid}");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.record.uuid', $record->uuid);
    }

    public function test_show_returns_404_for_nonexistent_record(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->baseUrl}/nonexistent-uuid")
            ->assertStatus(404);
    }

    public function test_user_cannot_access_record_belonging_to_another_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();
        $otherRecord = PetMedicalRecord::factory()
            ->forPet($otherPet)
            ->forUser($otherUser)
            ->create();

        // Try to access the record using own pet's URL
        $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->baseUrl}/{$otherRecord->uuid}")
            ->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function test_user_can_update_own_record(): void
    {
        $record = PetMedicalRecord::factory()
            ->forPet($this->pet)
            ->forUser($this->user)
            ->create(['title' => 'Old title']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->baseUrl}/{$record->uuid}", [
                'title' => 'Updated title',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.record.title', 'Updated title');

        $this->assertDatabaseHas('pet_medical_records', [
            'id' => $record->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_user_cannot_set_future_recorded_at_on_update(): void
    {
        $record = PetMedicalRecord::factory()
            ->forPet($this->pet)
            ->forUser($this->user)
            ->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->baseUrl}/{$record->uuid}", [
                'recorded_at' => now()->addDays(10)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_update_returns_404_for_nonexistent_record(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson("{$this->baseUrl}/nonexistent-uuid", [
                'title' => 'Update attempt',
            ])
            ->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function test_user_can_delete_own_record(): void
    {
        $record = PetMedicalRecord::factory()
            ->forPet($this->pet)
            ->forUser($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("{$this->baseUrl}/{$record->uuid}");

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSoftDeleted('pet_medical_records', ['id' => $record->id]);
    }

    public function test_delete_returns_404_for_nonexistent_record(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("{$this->baseUrl}/nonexistent-uuid")
            ->assertStatus(404);
    }

    // ─── Pet-scoped Appointment History ─────────────────────────────

    public function test_user_can_list_appointments_for_own_pet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/pets/{$this->pet->id}/appointments");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'pet_id',
                    'appointments',
                    'pagination',
                ],
            ]);
    }

    public function test_user_cannot_list_appointments_for_another_users_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/pets/{$otherPet->id}/appointments")
            ->assertStatus(404);
    }

    // ─── Pet-scoped Visit Records ────────────────────────────────────

    public function test_user_can_list_visit_records_for_own_pet(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/pets/{$this->pet->id}/visit-records");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'pet_id',
                    'visit_records',
                    'pagination',
                ],
            ]);
    }

    public function test_user_cannot_list_visit_records_for_another_users_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/pets/{$otherPet->id}/visit-records")
            ->assertStatus(404);
    }
}
