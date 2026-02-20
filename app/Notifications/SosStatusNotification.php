<?php

namespace App\Notifications;

use App\Models\SosRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private SosRequest $sosRequest,
        private string $previousStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sos_status_update',
            'sos_uuid' => $this->sosRequest->uuid,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->sosRequest->status,
            'resolution_notes' => $this->sosRequest->resolution_notes,
            'updated_at' => $this->sosRequest->updated_at?->toIso8601String(),
        ];
    }
}
