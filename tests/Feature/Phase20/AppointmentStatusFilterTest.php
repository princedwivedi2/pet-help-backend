<?php

namespace Tests\Feature\Phase20;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-05 RED test: GET /appointments?status= accepts CSV format.
 *
 * D-05: backend must split on comma, pass to whereIn.
 *   ?status=pending,confirmed   → only pending + confirmed
 *   ?status=completed,cancelled → completed + cancelled family
 *   ?status=pending             → single status (existing behaviour kept)
 *
 * RED wave: CSV filter returns ALL appointments because AppointmentService
 * treats 'pending,confirmed' as a single unknown status (byStatus() does a
 * simple ->where('status', ...) match which returns nothing or is ignored).
 * Plan 20-03 fixes AppointmentService::getUserAppointments() and
 * getVetAppointments() to split on comma.
 */
class AppointmentStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/appointments';

    private User $user;
    private VetProfile $vetProfile;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create(['user_id' => $vetUser->id]);
        $this->pet = Pet::factory()->forUser($this->user)->create();
    }

    /** Seed one appointment per status for the authed user. */
    private function seedAppointments(): void
    {
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            Appointment::factory()->create([
                'user_id'        => $this->user->id,
                'vet_profile_id' => $this->vetProfile->id,
                'pet_id'         => $this->pet->id,
                'status'         => $status,
            ]);
        }
    }

    /**
     * RED: CSV status=pending,confirmed must return ONLY pending and confirmed.
     * Currently the service sees 'pending,confirmed' as a single unknown status
     * so returns 0 results (or all, depending on fallback) — not the 2 expected.
     */
    public function test_csv_status_pending_confirmed(): void
    {
        $this->seedAppointments();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}?status=pending,confirmed");

        $response->assertOk();

        $appointments = $response->json('data.appointments', []);
        $this->assertNotEmpty($appointments, 'Should return at least some appointments for pending,confirmed filter.');

        $returnedStatuses = collect($appointments)->pluck('status')->unique()->values()->all();
        foreach ($returnedStatuses as $status) {
            $this->assertContains($status, ['pending', 'confirmed'],
                "Status '{$status}' should not be in pending,confirmed result set.");
        }

        // Expect exactly 2 (one pending + one confirmed from seeded data)
        $this->assertCount(2, $appointments,
            'CSV filter pending,confirmed must return exactly 2 appointments.');
    }

    /**
     * RED: status=completed,cancelled must return completed + cancelled family.
     * 'cancelled' should expand to cancelled/cancelled_by_user/cancelled_by_vet
     * per the existing service logic — CSV must trigger that path for each value.
     */
    public function test_csv_status_completed_cancelled(): void
    {
        $this->seedAppointments();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}?status=completed,cancelled");

        $response->assertOk();

        $appointments = $response->json('data.appointments', []);
        $this->assertNotEmpty($appointments, 'Should return appointments for completed,cancelled filter.');

        $allowedStatuses = ['completed', 'cancelled', 'cancelled_by_user', 'cancelled_by_vet'];
        $returnedStatuses = collect($appointments)->pluck('status')->unique()->values()->all();
        foreach ($returnedStatuses as $status) {
            $this->assertContains($status, $allowedStatuses,
                "Status '{$status}' must be in the completed/cancelled family.");
        }
    }

    /**
     * Single status must still work (backward compat).
     * This test should remain GREEN before and after the fix.
     */
    public function test_single_status_still_works(): void
    {
        $this->seedAppointments();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("{$this->prefix}?status=pending");

        $response->assertOk();

        $appointments = $response->json('data.appointments', []);
        $this->assertNotEmpty($appointments);

        $returnedStatuses = collect($appointments)->pluck('status')->unique()->values()->all();
        foreach ($returnedStatuses as $status) {
            $this->assertEquals('pending', $status,
                'Single-status filter must return only that status.');
        }
    }
}
