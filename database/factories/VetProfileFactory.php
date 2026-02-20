<?php

namespace Database\Factories;

use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VetProfileFactory extends Factory
{
    protected $model = VetProfile::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'clinic_name' => $this->faker->company() . ' Veterinary',
            'vet_name' => 'Dr. ' . $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'latitude' => $this->faker->latitude(40.5, 40.9),
            'longitude' => $this->faker->longitude(-74.2, -73.7),
            'services' => ['general', 'vaccination', 'dental'],
            'accepted_species' => ['dog', 'cat'],
            'is_emergency_available' => $this->faker->boolean(30),
            'is_24_hours' => $this->faker->boolean(10),
            'is_verified' => $this->faker->boolean(70),
            'is_active' => true,
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'review_count' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_emergency_available' => true,
        ]);
    }

    public function twentyFourHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_24_hours' => true,
            'is_emergency_available' => true,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
