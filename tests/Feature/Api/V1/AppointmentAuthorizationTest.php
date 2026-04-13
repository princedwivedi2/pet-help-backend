<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(): Appointment
    {
        $owner = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);

        $vetProfile = VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'approved',
            'verification_status' => 'approved',
            'is_active' => true,
        ]);

        $pet = Pet::factory()->forUser($owner)->create();

        return Appointment::factory()->create([
            'user_id' => $owner->id,
            'vet_profile_id' => $vetProfile->id,
            'pet_id' => $pet->id,
            'status' => 'pending',
            'appointment_type' => 'clinic_visit',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 30,
            'reason' => 'Routine checkup',
            'fee_amount' => 1500,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_admin_cannot_update_appointment_status(): void
    {
        $appointment = $this->makeAppointment();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/appointments/{$appointment->uuid}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Admins can only view appointments. Status updates are restricted to users and assigned vets.',
            ]);
    }

    public function test_assigned_vet_can_confirm_appointment(): void
    {
        $appointment = $this->makeAppointment();
        $vetUser = $appointment->vetProfile->user;

        $response = $this->actingAs($vetUser, 'sanctum')
            ->putJson("/api/v1/appointments/{$appointment->uuid}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'appointment' => [
                        'status' => 'confirmed',
                    ],
                ],
            ]);
    }

    public function test_owner_user_can_cancel_own_appointment(): void
    {
        $appointment = $this->makeAppointment();
        $owner = $appointment->user;

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/appointments/{$appointment->uuid}/status", [
                'status' => 'cancelled',
                'reason' => 'Owner cancelled',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'appointment' => [
                        'status' => 'cancelled_by_user',
                    ],
                ],
            ]);
    }
}
