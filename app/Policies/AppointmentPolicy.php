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
     * Confirm: the vet who received the appointment, or admin.
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->vetProfile?->user_id
            || $user->isAdmin();
    }

    /**
     * Complete: the vet who received the appointment, or admin.
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->vetProfile?->user_id
            || $user->isAdmin();
    }

    /**
     * Cancel: the owner, the vet, or admin.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->id === $appointment->vetProfile?->user_id
            || $user->isAdmin();
    }
}
