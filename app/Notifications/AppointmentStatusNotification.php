<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Appointment $appointment,
        private string $previousStatus
    ) {}

    /**
     * Delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $messages = [
            'accepted' => 'Your appointment request has been accepted by the vet',
            'rejected' => 'Your appointment request was rejected by the vet',
            'confirmed' => 'Your appointment has been confirmed',
            'in_progress' => 'Your appointment visit has started',
            'completed' => 'Your appointment has been marked as completed',
            'cancelled' => 'Your appointment has been cancelled',
            'cancelled_by_user' => 'The appointment was cancelled by the pet owner',
            'cancelled_by_vet' => 'The appointment was cancelled by the vet',
            'no_show'   => 'Your appointment was marked as no-show',
        ];

        return [
            'type'              => 'appointment_status_changed',
            'appointment_uuid'  => $this->appointment->uuid,
            'previous_status'   => $this->previousStatus,
            'new_status'        => $this->appointment->status,
            'vet_name'          => $this->appointment->vetProfile?->vet_name ?? 'Vet',
            'clinic_name'       => $this->appointment->vetProfile?->clinic_name,
            'scheduled_at'      => $this->appointment->scheduled_at?->toIso8601String(),
            'message'           => $messages[$this->appointment->status] ?? 'Appointment status updated',
            'rejection_reason'  => $this->appointment->rejection_reason,
            'cancellation_reason' => $this->appointment->cancellation_reason,
        ];
    }
}
