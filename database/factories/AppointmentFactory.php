<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vet_profile_id' => VetProfile::factory(),
            'pet_id' => Pet::factory(),
            'status' => 'pending',
            'appointment_type' => 'clinic_visit',
            'is_emergency' => false,
            'scheduled_at' => now()->addDays(3)->setHour(10)->setMinute(0),
            'duration_minutes' => 30,
            'reason' => $this->faker->sentence(8),
            'notes' => null,
            'fee_amount' => 500,
            'payment_status' => 'unpaid',
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forVet(VetProfile $vetProfile): static
    {
        return $this->state(fn (array $attributes) => [
            'vet_profile_id' => $vetProfile->id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'accepted_at' => now()->subHour(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'accepted_at' => now()->subHours(2),
            'visit_started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'accepted_at' => now()->subHours(3),
            'visit_started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    public function homeVisit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'appointment_type' => 'home_visit',
                'home_address' => $attributes['home_address'] ?? $this->faker->address(),
                'home_latitude' => $attributes['home_latitude'] ?? $this->faker->latitude(40.5, 40.9),
                'home_longitude' => $attributes['home_longitude'] ?? $this->faker->longitude(-74.2, -73.7),
            ];
        });
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'appointment_type' => 'online',
        ]);
    }
}
