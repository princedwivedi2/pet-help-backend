<?php

namespace Database\Factories;

use App\Models\IncidentLog;
use App\Models\User;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentLogFactory extends Factory
{
    protected $model = IncidentLog::class;

    public function definition(): array
    {
        $incidentTypes = ['emergency', 'routine_visit', 'vaccination', 'surgery', 'medication', 'other'];
        $statuses = ['open', 'in_treatment', 'resolved', 'follow_up_required'];

        return [
            'user_id' => User::factory(),
            'pet_id' => Pet::factory(),
            'sos_request_id' => null,
            'vet_profile_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'incident_type' => $this->faker->randomElement($incidentTypes),
            'status' => $this->faker->randomElement($statuses),
            'incident_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'follow_up_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 month'),
            'attachments' => null,
            'vet_notes' => $this->faker->optional(0.5)->paragraph(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => 'emergency',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }
}
