<?php

namespace App\Policies;

use App\Models\IncidentLog;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Any authenticated user can list their own incidents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only the incident owner can view a specific incident.
     */
    public function view(User $user, IncidentLog $incidentLog): bool
    {
        return $user->id === $incidentLog->user_id;
    }
}
