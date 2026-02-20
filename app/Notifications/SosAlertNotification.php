<?php

namespace App\Notifications;

use App\Models\SosRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private SosRequest $sosRequest
    ) {}

    /**
     * Delivery channels.
     *
     * Currently: database only (in-app notifications).
     * Future: add 'fcm' or 'broadcast' for real-time push.
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
            'type' => 'sos_alert',
            'sos_uuid' => $this->sosRequest->uuid,
            'emergency_type' => $this->sosRequest->emergency_type,
            'description' => $this->sosRequest->description,
            'latitude' => $this->sosRequest->latitude,
            'longitude' => $this->sosRequest->longitude,
            'user_name' => $this->sosRequest->user?->name ?? 'Unknown',
            'pet_name' => $this->sosRequest->pet?->name,
            'created_at' => $this->sosRequest->created_at?->toIso8601String(),
        ];
    }
}
