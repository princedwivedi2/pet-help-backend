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
            'confirmed' => 'Your appointment has been confirmed',
            'completed' => 'Your appointment has been marked as completed',
            'cancelled' => 'Your appointment has been cancelled',
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
        ];
    }
}
