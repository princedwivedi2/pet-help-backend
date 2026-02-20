<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        $species = ['dog', 'cat', 'bird', 'rabbit', 'hamster', 'fish', 'reptile', 'other'];
        $breeds = [
            'dog' => ['Golden Retriever', 'Labrador', 'German Shepherd', 'Bulldog', 'Beagle', 'Poodle'],
            'cat' => ['Persian', 'Siamese', 'Maine Coon', 'British Shorthair', 'Ragdoll'],
            'bird' => ['Parakeet', 'Cockatiel', 'Parrot', 'Canary'],
            'rabbit' => ['Holland Lop', 'Mini Rex', 'Netherland Dwarf'],
            'hamster' => ['Syrian', 'Dwarf', 'Chinese'],
            'fish' => ['Goldfish', 'Betta', 'Guppy', 'Tetra'],
            'reptile' => ['Leopard Gecko', 'Bearded Dragon', 'Ball Python'],
            'other' => ['Mixed', 'Unknown'],
        ];

        $selectedSpecies = $this->faker->randomElement($species);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->firstName(),
            'species' => $selectedSpecies,
            'breed' => $this->faker->randomElement($breeds[$selectedSpecies]),
            'birth_date' => $this->faker->dateTimeBetween('-15 years', '-1 month'),
            'weight_kg' => $this->faker->randomFloat(2, 0.1, 100),
            'photo_url' => null,
            'medical_notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function dog(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => 'dog',
            'breed' => $this->faker->randomElement(['Golden Retriever', 'Labrador', 'German Shepherd']),
        ]);
    }

    public function cat(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => 'cat',
            'breed' => $this->faker->randomElement(['Persian', 'Siamese', 'Maine Coon']),
        ]);
    }
}
