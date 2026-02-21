<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Appointment booking interface.
 *
 * Stub for future implementation. Will coordinate
 * pet owner appointments with verified vets.
 */
interface AppointmentBooking
{
    /**
     * List available time slots for a vet.
     *
     * @param string $vetUuid
     * @param string $date  Y-m-d format
     * @return array Array of available slot objects
     */
    public function getAvailableSlots(string $vetUuid, string $date): array;

    /**
     * Book an appointment.
     *
     * @param User   $user
     * @param string $vetUuid
     * @param string $dateTime  ISO 8601
     * @param array  $details   ['pet_id', 'reason', 'notes', ...]
     * @return array ['appointment_id' => string, 'status' => string, ...]
     */
    public function book(User $user, string $vetUuid, string $dateTime, array $details = []): array;

    /**
     * Cancel an existing appointment.
     *
     * @param string $appointmentId
     * @param User   $user
     * @return array ['cancelled' => bool, ...]
     */
    public function cancel(string $appointmentId, User $user): array;
}
