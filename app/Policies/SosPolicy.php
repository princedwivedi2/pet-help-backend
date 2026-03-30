<?php

namespace App\Policies;

use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;

class SosPolicy
{
    /**
     * LOW-01 FIX: Owner, assigned vet, or admin can view SOS.
     */
    public function view(User $user, SosRequest $sosRequest): bool
    {
        if ($user->id === $sosRequest->user_id || $user->isAdmin()) {
            return true;
        }

        // Assigned vet can view
        if ($sosRequest->assigned_vet_id) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            if ($vetProfile && $vetProfile->id === $sosRequest->assigned_vet_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Any authenticated user can create an SOS (rate limit in controller).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * LOW-01 FIX: Owner, assigned vet, or admin can update SOS status.
     */
    public function update(User $user, SosRequest $sosRequest): bool
    {
        if ($user->id === $sosRequest->user_id || $user->isAdmin()) {
            return true;
        }

        // Assigned vet can update
        if ($sosRequest->assigned_vet_id) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            if ($vetProfile && $vetProfile->id === $sosRequest->assigned_vet_id) {
                return true;
            }
        }

        // Any emergency-available vet can accept unassigned SOS
        if (!$sosRequest->assigned_vet_id && in_array($sosRequest->status, ['pending', 'sos_pending'])) {
            $vetProfile = VetProfile::where('user_id', $user->id)->active()->verified()->first();
            return $vetProfile && $vetProfile->is_emergency_available;
        }

        return false;
    }
}
