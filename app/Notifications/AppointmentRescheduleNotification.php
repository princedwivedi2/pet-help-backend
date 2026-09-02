<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Google-Meet-style reschedule request lifecycle:
 *   requested → sent to the vet when the pet owner proposes a new time.
 *   accepted  → sent to the pet owner once the vet accepts the new time.
 *   rejected  → sent to the pet owner if the vet declines it.
 */
class AppointmentRescheduleNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Appointment $appointment,
        private string $event, // requested | accepted | rejected
        private ?string $proposedAt = null,
        private ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    private function title(): string
    {
        return match ($this->event) {
            'requested' => 'Reschedule Requested',
            'accepted'  => 'Reschedule Accepted',
            'rejected'  => 'Reschedule Declined',
            default     => 'Appointment Update',
        };
    }

    private function body(): string
    {
        return match ($this->event) {
            'requested' => 'The pet owner requested a new time for this appointment. Review and accept or decline.',
            'accepted'  => 'The vet accepted your requested time. Your appointment is confirmed for the new slot.',
            'rejected'  => 'The vet was unable to accept your requested time. The original slot still stands.',
            default     => 'Your appointment reschedule request was updated.',
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'appointment_reschedule_' . $this->event,
            'title'            => $this->title(),
            'appointment_uuid' => $this->appointment->uuid,
            'event'            => $this->event,
            'proposed_at'      => $this->proposedAt,
            'reason'           => $this->reason,
            'scheduled_at'     => $this->appointment->scheduled_at?->toIso8601String(),
            'message'          => $this->body(),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body'  => $this->body(),
            'data'  => [
                'type'             => 'appointment_reschedule_' . $this->event,
                'appointment_uuid' => $this->appointment->uuid,
            ],
        ];
    }
}
