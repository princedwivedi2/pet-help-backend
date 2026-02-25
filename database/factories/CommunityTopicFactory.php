<?php

namespace Database\Factories;

use App\Models\CommunityTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityTopicFactory extends Factory
{
    protected $model = CommunityTopic::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->optional(0.5)->emoji(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
