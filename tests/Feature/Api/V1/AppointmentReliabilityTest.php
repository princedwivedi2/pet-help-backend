<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/appointments';

    public function test_cannot_book_appointment_in_past(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $pet = Pet::factory()->forUser($owner)->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $vet->uuid,
                'pet_id' => $pet->id,
                'scheduled_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'reason' => 'Past appointment test',
            ]);

        $response->assertStatus(422);
    }

    public function test_duration_overlap_is_prevented(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);

        $petOne = Pet::factory()->forUser($owner)->create();
        $petTwo = Pet::factory()->forUser($otherUser)->create();

        $startTime = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $first = $this->actingAs($owner, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $vet->uuid,
                'pet_id' => $petOne->id,
                'scheduled_at' => $startTime->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'reason' => 'First booking',
            ]);

        $first->assertStatus(201);

        $second = $this->actingAs($otherUser, 'sanctum')
            ->postJson($this->prefix, [
                'vet_uuid' => $vet->uuid,
                'pet_id' => $petTwo->id,
                'scheduled_at' => $startTime->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'reason' => 'Overlapping booking attempt',
            ]);

        $second->assertStatus(409);
    }

    public function test_expire_stale_marks_past_pending_appointments_cancelled(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $pet = Pet::factory()->forUser($owner)->create();

        $appointment = Appointment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'vet_profile_id' => $vet->id,
            'pet_id' => $pet->id,
            'status' => 'pending',
            'appointment_type' => 'clinic_visit',
            'scheduled_at' => now()->subHours(3),
            'duration_minutes' => 30,
            'reason' => 'Stale pending appointment',
            'fee_amount' => 500,
            'payment_status' => 'unpaid',
        ]);

        $affected = app(AppointmentService::class)->expireStale();

        $this->assertGreaterThan(0, $affected);
        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_waitlist_notification_processing_marks_entry_notified(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);
        $waitlistUser = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $vet = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $pet = Pet::factory()->forUser($owner)->create();

        $cancelledAppointment = Appointment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'vet_profile_id' => $vet->id,
            'pet_id' => $pet->id,
            'status' => 'cancelled',
            'appointment_type' => 'clinic_visit',
            'scheduled_at' => now()->addDay()->setHour(11)->setMinute(0)->setSecond(0),
            'duration_minutes' => 30,
            'reason' => 'Cancelled slot',
            'fee_amount' => 500,
            'payment_status' => 'unpaid',
        ]);

        $cancelledAppointment->update(['updated_at' => now()]);

        $entry = AppointmentWaitlist::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $waitlistUser->id,
            'vet_profile_id' => $vet->id,
            'pet_id' => null,
            'preferred_date' => $cancelledAppointment->scheduled_at->toDateString(),
            'consultation_type' => 'clinic',
            'is_notified' => false,
            'expires_at' => now()->addDay(),
        ]);

        $notified = app(AppointmentService::class)->processWaitlistNotifications();

        $this->assertGreaterThan(0, $notified);
        $this->assertTrue($entry->fresh()->is_notified);
        $this->assertNotNull($entry->fresh()->notified_at);
    }
}
