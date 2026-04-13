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
     * Determine whether the user can view the pet.
     */
    public function view(User $user, Pet $pet): bool
    {
        // Pet owner can always view
        if ($user->id === $pet->user_id) {
            return true;
        }

        // Vets can view pets they have appointments or SOS requests with
        if ($user->isVet()) {
            $vetProfile = $user->vetProfile;
            if ($vetProfile) {
                // Check if vet has any appointments with this pet
                $hasAppointment = $pet->user->appointments()
                    ->where('vet_profile_id', $vetProfile->id)
                    ->exists();

                // Check if vet has any SOS requests with this pet
                $hasSosRequest = $pet->sosRequests()
                    ->where('assigned_vet_id', $vetProfile->id)
                    ->exists();

                return $hasAppointment || $hasSosRequest;
            }
        }

        // Admins can view any pet
        return $user->isAdmin();
    }

    /**
     * Any authenticated user can create pets (limit enforced in service).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the pet.
     */
    public function update(User $user, Pet $pet): bool
    {
        // Only pet owner can update their pet's information
        return $user->id === $pet->user_id;
    }

    /**
     * Determine whether the user can delete the pet.
     */
    public function delete(User $user, Pet $pet): bool
    {
        // Only pet owner can delete their pet
        return $user->id === $pet->user_id;
    }

    /**
     * Determine whether the user can add medications to the pet.
     */
    public function addMedication(User $user, Pet $pet): bool
    {
        // Pet owner can always add medications
        if ($user->id === $pet->user_id) {
            return true;
        }

        // Vets can add medications if they have active relationship with pet
        if ($user->isVet()) {
            $vetProfile = $user->vetProfile;
            if ($vetProfile) {
                return $pet->user->appointments()
                    ->where('vet_profile_id', $vetProfile->id)
                    ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view confidential documents.
     */
    public function viewConfidentialDocuments(User $user, Pet $pet): bool
    {
        // Only pet owner can view confidential documents
        return $user->id === $pet->user_id;
    }
}
