<?php

namespace Database\Factories;

use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VetProfileFactory extends Factory
{
    protected $model = VetProfile::class;

    /**
     * vet_status / verification_status / is_active are intentionally excluded from
     * VetProfile $fillable (security: prevent self-approval). Factory sets them via
     * forceFill in afterMaking — state methods that override these add another
     * afterMaking which runs later and wins.
     */
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
            'languages' => ['en'],
            'is_emergency_available' => $this->faker->boolean(30),
            'is_24_hours' => $this->faker->boolean(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (VetProfile $vetProfile) {
            // Defaults — state methods queue another afterMaking that runs after this and wins.
            if ($vetProfile->vet_status === null) {
                $vetProfile->forceFill(['vet_status' => 'pending']);
            }
            if ($vetProfile->is_active === null) {
                $vetProfile->forceFill(['is_active' => true]);
            }
        });
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
        return $this->afterMaking(function (VetProfile $vetProfile) {
            $vetProfile->forceFill(['vet_status' => 'approved']);
        });
    }

    public function inactive(): static
    {
        return $this->afterMaking(function (VetProfile $vetProfile) {
            $vetProfile->forceFill(['is_active' => false]);
        });
    }
}
