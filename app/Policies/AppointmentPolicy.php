<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * View: owner, the vet, or admin.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->id === $appointment->vetProfile?->user_id
            || $user->isAdmin();
    }

    /**
     * Any authenticated user can create an appointment.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Confirm: only the vet who received the appointment.
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->vetProfile?->user_id;
    }

    /**
     * Complete: only the vet who received the appointment.
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->vetProfile?->user_id;
    }

    /**
     * Cancel: only the owner or the assigned vet.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->id === $appointment->vetProfile?->user_id;
    }
}
