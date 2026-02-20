<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\User;

class PetPolicy
{
    /**
     * Any authenticated user can list their own pets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only the pet owner can view a specific pet.
     */
    public function view(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id;
    }

    /**
     * Any authenticated user can create pets (limit enforced in service).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the pet owner can update a pet.
     */
    public function update(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id;
    }

    /**
     * Only the pet owner can delete a pet.
     */
    public function delete(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id;
    }
}
