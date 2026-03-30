<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
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
                'consultation_fee' => 600,
                'consultation_types' => ['clinic_visit', 'online', 'home_visit'],
                'latitude' => 19.0700,
                'longitude' => 72.8700,
            ], $overrides))
            ->create();
    }

    public function test_user_can_book_future_slot_with_approved_vet(): void
    {
        $user = User::factory()->create();
        $pet = Pet::factory()->forUser($user)->create();
        $vet = $this->makeApprovedVet();

        Sanctum::actingAs($user);

        $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);

        $response = $this->postJson('/api/v1/appointments', [
            'vet_uuid' => $vet->uuid,
            'pet_id' => $pet->id,
            'scheduled_at' => $scheduledAt->toISOString(),
            'reason' => 'General health check',
            'appointment_type' => 'clinic_visit',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'user_id' => $user->id,
            'vet_profile_id' => $vet->id,
            'pet_id' => $pet->id,
            'status' => 'pending',
        ]);
    }

    public function test_past_date_booking_is_rejected(): void
    {
        $user = User::factory()->create();
        $pet = Pet::factory()->forUser($user)->create();
        $vet = $this->makeApprovedVet();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/appointments', [
            'vet_uuid' => $vet->uuid,
            'pet_id' => $pet->id,
            'scheduled_at' => Carbon::now()->subDay()->toISOString(),
            'reason' => 'Backdated attempt',
            'appointment_type' => 'clinic_visit',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $user = User::factory()->create();
        $pet = Pet::factory()->forUser($user)->create();
        $vet = $this->makeApprovedVet();

        Sanctum::actingAs($user);

        $baseTime = Carbon::now()->addDays(3)->setTime(10, 0);

        Appointment::factory()
            ->forUser($user)
            ->forVet($vet)
            ->state([
                'pet_id' => $pet->id,
                'scheduled_at' => $baseTime,
                'duration_minutes' => 30,
            ])
            ->create();

        $response = $this->postJson('/api/v1/appointments', [
            'vet_uuid' => $vet->uuid,
            'pet_id' => $pet->id,
            'scheduled_at' => $baseTime->copy()->addMinutes(15)->toISOString(),
            'duration_minutes' => 30,
            'reason' => 'Overlap attempt',
            'appointment_type' => 'clinic_visit',
        ]);

        $response->assertStatus(409);
        $response->assertJsonFragment([
            'message' => 'This time slot overlaps with an existing booking.',
        ]);
    }

    public function test_pet_must_belong_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignPet = Pet::factory()->forUser($otherUser)->create();
        $vet = $this->makeApprovedVet();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/appointments', [
            'vet_uuid' => $vet->uuid,
            'pet_id' => $foreignPet->id,
            'scheduled_at' => Carbon::now()->addDays(1)->toISOString(),
            'reason' => 'Wrong pet ownership',
            'appointment_type' => 'clinic_visit',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pet_id']);
    }
}
