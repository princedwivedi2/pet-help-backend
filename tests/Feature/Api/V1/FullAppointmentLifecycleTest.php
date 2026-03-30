<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full Appointment Lifecycle QA Test
 * Flow: Book → Accept → Start Visit → Complete → Payment
 */
class FullAppointmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/appointments';
    private User $owner;
    private User $vetUser;
    private VetProfile $vetProfile;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'user']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $this->vetUser->id,
            'consultation_fee' => 500,
            'home_visit_fee' => 800,
            'consultation_types' => ['clinic_visit', 'home_visit', 'online'],
        ]);
        $this->pet = Pet::factory()->forUser($this->owner)->create();
    }

    // ─── 1. Book Appointment ─────────────────────────────────────────

    public function test_user_can_book_clinic_visit(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => now()->addDays(3)->setHour(10)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'reason' => 'Annual checkup for my pet dog',
                'appointment_type' => 'clinic_visit',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.appointment.status', 'pending')
            ->assertJsonPath('data.appointment.appointment_type', 'clinic_visit')
            ->assertJsonPath('data.appointment.fee_amount', 500);
    }

    public function test_user_can_book_home_visit(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => now()->addDays(3)->setHour(14)->format('Y-m-d H:i:s'),
                'duration_minutes' => 45,
                'reason' => 'Pet needs home visit due to mobility issues',
                'appointment_type' => 'home_visit',
                'home_address' => '123 Main St, Test City',
                'home_latitude' => 40.7128,
                'home_longitude' => -74.006,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.appointment.appointment_type', 'home_visit')
            ->assertJsonPath('data.appointment.fee_amount', 800);
    }

    public function test_booking_requires_pet(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason' => 'Annual checkup for my pet',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_book_with_unapproved_vet(): void
    {
        $unapprovedVet = VetProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'vet'])->id,
            'vet_status' => 'pending',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $unapprovedVet->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason' => 'Annual checkup for my pet dog',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_book_other_users_pet(): void
    {
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $otherPet->id,
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason' => 'Trying to book with someone elses pet',
            ]);

        $response->assertStatus(422);
    }

    public function test_double_booking_same_slot_prevented(): void
    {
        $scheduledAt = now()->addDays(3)->setHour(10)->format('Y-m-d H:i:s');

        // First booking
        $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => $scheduledAt,
                'reason' => 'First booking for this slot',
            ])->assertStatus(201);

        // Second booking at same time
        $otherUser = User::factory()->create();
        $otherPet = Pet::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $otherPet->id,
                'scheduled_at' => $scheduledAt,
                'reason' => 'Second booking attempt same slot',
            ]);

        $response->assertStatus(409);
    }

    public function test_home_visit_requires_address(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'reason' => 'Need home visit but no address',
                'appointment_type' => 'home_visit',
            ]);

        $response->assertStatus(422);
    }

    // ─── 2. Vet Accepts Appointment ─────────────────────────────────

    public function test_vet_can_accept_pending_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/accept");

        $response->assertOk()
            ->assertJsonPath('data.appointment.status', 'accepted');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'accepted',
        ]);
    }

    public function test_vet_can_reject_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/reject", [
                'reason' => 'Fully booked for this time slot',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.appointment.status', 'rejected');
    }

    public function test_non_assigned_vet_cannot_accept(): void
    {
        $appointment = $this->createPendingAppointment();
        $otherVetUser = User::factory()->create(['role' => 'vet']);
        VetProfile::factory()->verified()->create(['user_id' => $otherVetUser->id]);

        $response = $this->actingAs($otherVetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/accept");

        $response->assertStatus(403);
    }

    // ─── 3. Start Visit (In Progress) ───────────────────────────────

    public function test_vet_can_start_visit(): void
    {
        $appointment = $this->createAcceptedAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/start", [
                'latitude' => 40.7128,
                'longitude' => -74.006,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.appointment.status', 'in_progress');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_pending_appointment_cannot_start_visit(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/start");

        $response->assertStatus(422);
    }

    // ─── 4. Complete Appointment ─────────────────────────────────────

    public function test_vet_can_complete_in_progress_appointment(): void
    {
        $appointment = $this->createInProgressAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/complete", [
                'notes' => 'Treatment completed successfully. Follow-up in 2 weeks.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.appointment.status', 'completed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }

    public function test_cannot_complete_pending_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/complete");

        $response->assertStatus(422);
    }

    // ─── 5. Cancel Appointment ───────────────────────────────────────

    public function test_owner_can_cancel_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/cancel", [
                'reason' => 'Pet is feeling better now',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled_by_user',
        ]);
    }

    public function test_vet_can_cancel_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/cancel", [
                'reason' => 'Emergency situation, need to reschedule',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled_by_vet',
        ]);
    }

    public function test_cancel_requires_reason(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/cancel", []);

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_completed_appointment(): void
    {
        $appointment = $this->createCompletedAppointment();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/cancel", [
                'reason' => 'Trying to cancel after completion',
            ]);

        $response->assertStatus(422);
    }

    // ─── 6. Full Lifecycle Flow ──────────────────────────────────────

    public function test_full_appointment_lifecycle(): void
    {
        // Step 1: User books appointment
        $bookResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $this->vetProfile->uuid,
                'pet_id' => $this->pet->id,
                'scheduled_at' => now()->addDays(3)->setHour(10)->format('Y-m-d H:i:s'),
                'reason' => 'Full lifecycle test appointment',
            ]);

        $bookResponse->assertStatus(201);
        $uuid = $bookResponse->json('data.appointment.uuid');
        $this->assertNotNull($uuid);

        // Step 2: Vet accepts
        $acceptResponse = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$uuid}/accept");
        $acceptResponse->assertOk()
            ->assertJsonPath('data.appointment.status', 'accepted');

        // Step 3: Vet starts visit
        $startResponse = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$uuid}/start", [
                'latitude' => 40.7128,
                'longitude' => -74.006,
            ]);
        $startResponse->assertOk()
            ->assertJsonPath('data.appointment.status', 'in_progress');

        // Step 4: Vet completes
        $completeResponse = $this->actingAs($this->vetUser, 'sanctum')
            ->patchJson("{$this->prefix}/{$uuid}/complete", [
                'notes' => 'All done. Pet is healthy.',
            ]);
        $completeResponse->assertOk()
            ->assertJsonPath('data.appointment.status', 'completed');

        // Verify final DB state
        $this->assertDatabaseHas('appointments', [
            'uuid' => $uuid,
            'status' => 'completed',
        ]);
    }

    // ─── 7. View & List ─────────────────────────────────────────────

    public function test_user_can_list_appointments(): void
    {
        Appointment::factory()->forUser($this->owner)->forVet($this->vetProfile)->create([
            'pet_id' => $this->pet->id,
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson($this->prefix);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_vet_can_list_vet_appointments(): void
    {
        Appointment::factory()->forUser($this->owner)->forVet($this->vetProfile)->create([
            'pet_id' => $this->pet->id,
        ]);

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->getJson("{$this->prefix}/vet");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['appointments', 'pagination']]);
    }

    public function test_user_can_view_own_appointment(): void
    {
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("{$this->prefix}/{$appointment->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.appointment.uuid', $appointment->uuid);
    }

    // ─── 8. Admin Restrictions ───────────────────────────────────────

    public function test_admin_cannot_update_appointment_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = $this->createPendingAppointment();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("{$this->prefix}/{$appointment->uuid}/accept");

        $response->assertStatus(403);
    }

    // ─── 9. No-Show ─────────────────────────────────────────────────

    public function test_vet_can_mark_no_show(): void
    {
        $appointment = $this->createConfirmedAppointment();

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->putJson("{$this->prefix}/{$appointment->uuid}/status", [
                'status' => 'no_show',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'no_show',
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createPendingAppointment(): Appointment
    {
        return Appointment::factory()
            ->forUser($this->owner)
            ->forVet($this->vetProfile)
            ->create(['pet_id' => $this->pet->id]);
    }

    private function createAcceptedAppointment(): Appointment
    {
        return Appointment::factory()
            ->forUser($this->owner)
            ->forVet($this->vetProfile)
            ->accepted()
            ->create(['pet_id' => $this->pet->id]);
    }

    private function createConfirmedAppointment(): Appointment
    {
        return Appointment::factory()
            ->forUser($this->owner)
            ->forVet($this->vetProfile)
            ->confirmed()
            ->create(['pet_id' => $this->pet->id]);
    }

    private function createInProgressAppointment(): Appointment
    {
        return Appointment::factory()
            ->forUser($this->owner)
            ->forVet($this->vetProfile)
            ->inProgress()
            ->create(['pet_id' => $this->pet->id]);
    }

    private function createCompletedAppointment(): Appointment
    {
        return Appointment::factory()
            ->forUser($this->owner)
            ->forVet($this->vetProfile)
            ->completed()
            ->create(['pet_id' => $this->pet->id]);
    }
}
