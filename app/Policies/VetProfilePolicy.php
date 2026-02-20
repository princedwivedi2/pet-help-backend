<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VetProfile;

class VetProfilePolicy
{
    /**
     * Only the vet owner can view their own unverified profile.
     * Verified profiles are publicly accessible via VetController.
     */
    public function view(User $user, VetProfile $vetProfile): bool
    {
        return $user->id === $vetProfile->user_id || $user->isAdmin();
    }

    /**
     * Only the vet owner can update their profile.
     */
    public function update(User $user, VetProfile $vetProfile): bool
    {
        return $user->id === $vetProfile->user_id;
    }

    /**
     * Only admins can verify vet profiles.
     */
    public function verify(User $user): bool
    {
        return $user->isAdmin();
    }
}
