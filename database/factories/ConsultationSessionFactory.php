<?php

namespace Database\Factories;

use App\Models\ConsultationSession;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ConsultationSession>
 */
class ConsultationSessionFactory extends Factory
{
    protected $model = ConsultationSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vet_profile_id' => null,
            'pet_id' => null,
            'origin' => 'instant',
            'modality' => 'video',
            'status' => 'pending',
        ];
    }

    public function matched(): static
    {
        return $this->state(fn () => [
            'vet_profile_id' => VetProfile::factory()->verified(),
            'status' => 'matched',
            'matched_at' => now(),
            'vet_no_show_check_at' => now()->addMinutes(10),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'vet_profile_id' => VetProfile::factory()->verified(),
            'status' => 'active',
            'matched_at' => now()->subMinutes(2),
            'started_at' => now()->subMinute(),
        ]);
    }
}
