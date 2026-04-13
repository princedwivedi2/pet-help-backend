<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\PetMedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetMedicalRecordFactory extends Factory
{
    protected $model = PetMedicalRecord::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            'diagnosis', 'vaccination', 'medicine', 'lab_report', 'general',
        ]);

        $data = [
            'pet_id' => Pet::factory(),
            'recorded_by_user_id' => User::factory(),
            'recorded_by_vet_id' => null,
            'visit_record_id' => null,
            'record_type' => $type,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'medicine_name' => null,
            'medicine_dosage' => null,
            'medicine_frequency' => null,
            'medicine_duration' => null,
            'attachment_url' => null,
            'recorded_at' => $this->faker->dateTimeBetween('-2 years', 'today')->format('Y-m-d'),
        ];

        if ($type === 'medicine') {
            $data['medicine_name'] = $this->faker->randomElement([
                'Amoxicillin', 'Metacam', 'Drontal', 'Frontline', 'Heartgard', 'Rimadyl',
            ]);
            $data['medicine_dosage'] = $this->faker->randomElement([
                '5mg', '10mg', '25mg/kg', '1 tablet',
            ]);
            $data['medicine_frequency'] = $this->faker->randomElement([
                'Once daily', 'Twice daily', 'Every 8 hours', 'Weekly',
            ]);
            $data['medicine_duration'] = $this->faker->randomElement([
                '7 days', '10 days', '1 month', 'Ongoing',
            ]);
        }

        return $data;
    }

    public function forPet(Pet $pet): static
    {
        return $this->state(fn (array $attributes) => [
            'pet_id' => $pet->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_by_user_id' => $user->id,
        ]);
    }

    public function ofType(string $type): static
    {
        $state = ['record_type' => $type];

        if ($type === 'medicine') {
            $state['medicine_name'] = 'Amoxicillin';
            $state['medicine_dosage'] = '10mg';
            $state['medicine_frequency'] = 'Twice daily';
            $state['medicine_duration'] = '7 days';
        }

        return $this->state(fn (array $attributes) => $state);
    }

    public function diagnosis(): static
    {
        return $this->ofType('diagnosis');
    }

    public function vaccination(): static
    {
        return $this->ofType('vaccination');
    }

    public function medicine(): static
    {
        return $this->ofType('medicine');
    }
}
