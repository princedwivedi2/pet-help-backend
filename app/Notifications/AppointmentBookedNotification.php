<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentBookedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Appointment $appointment
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
        return [
            'type'             => 'appointment_booked',
            'appointment_uuid' => $this->appointment->uuid,
            'user_name'        => $this->appointment->user?->name ?? 'Unknown',
            'pet_name'         => $this->appointment->pet?->name,
            'reason'           => $this->appointment->reason,
            'scheduled_at'     => $this->appointment->scheduled_at?->toIso8601String(),
            'duration_minutes' => $this->appointment->duration_minutes,
            'message'          => "New appointment request from {$this->appointment->user?->name}",
        ];
    }
}
