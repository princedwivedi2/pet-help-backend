<?php

namespace App\Policies;

use App\Models\SosRequest;
use App\Models\User;

class SosPolicy
{
    /**
     * Only the SOS owner can view their request.
     */
    public function view(User $user, SosRequest $sosRequest): bool
    {
        return $user->id === $sosRequest->user_id;
    }

    /**
     * Any authenticated user can create an SOS (rate limit in controller).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the SOS owner can update its status.
     */
    public function update(User $user, SosRequest $sosRequest): bool
    {
        return $user->id === $sosRequest->user_id;
    }
}
