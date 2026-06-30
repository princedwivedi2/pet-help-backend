<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentBookedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Appointment $appointment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

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

    public function toFcm(object $notifiable): array
    {
        $userName = $this->appointment->user?->name ?? 'A client';
        $petName  = $this->appointment->pet?->name;

        return [
            'title' => 'New Appointment Request',
            'body'  => $petName
                ? "{$userName} booked an appointment for {$petName}"
                : "{$userName} has requested an appointment",
            'data'  => [
                'type'             => 'appointment_booked',
                'appointment_uuid' => $this->appointment->uuid,
            ],
        ];
    }
}
