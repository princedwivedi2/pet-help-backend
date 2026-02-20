<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PetService
{
    public const MAX_PETS_PER_USER = 10;

    public function createPet(User $user, array $data): Pet
    {
        return $user->pets()->create($data);
    }

    public function updatePet(Pet $pet, array $data): Pet
    {
        $pet->update($data);
        return $pet->fresh();
    }

    public function deletePet(Pet $pet): bool
    {
        return $pet->delete();
    }

    public function getUserPets(User $user): Collection
    {
        return $user->pets()->orderBy('name')->get();
    }

    public function findPetForUser(User $user, int $petId): ?Pet
    {
        return $user->pets()->find($petId);
    }

    public function canUserCreatePet(User $user): bool
    {
        return $user->pets()->count() < self::MAX_PETS_PER_USER;
    }

    public function getUserPetCount(User $user): int
    {
        return $user->pets()->count();
    }
}
