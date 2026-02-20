<?php

namespace Database\Factories;

use App\Models\SosRequest;
use App\Models\User;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

class SosRequestFactory extends Factory
{
    protected $model = SosRequest::class;

    public function definition(): array
    {
        $emergencyTypes = ['injury', 'illness', 'poisoning', 'accident', 'breathing', 'seizure', 'other'];
        $statuses = ['pending', 'acknowledged', 'in_progress', 'completed', 'cancelled'];

        return [
            'user_id' => User::factory(),
            'pet_id' => Pet::factory(),
            'latitude' => $this->faker->latitude(40.5, 40.9),
            'longitude' => $this->faker->longitude(-74.2, -73.7),
            'address' => $this->faker->address(),
            'description' => $this->faker->paragraph(),
            'emergency_type' => $this->faker->randomElement($emergencyTypes),
            'status' => 'pending',
            'assigned_vet_id' => null,
            'acknowledged_at' => null,
            'completed_at' => null,
            'resolution_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'acknowledged_at' => now()->subHour(),
            'completed_at' => now(),
            'resolution_notes' => $this->faker->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
