<?php

namespace App\Notifications;

use App\Models\SosRequest;
use App\Notifications\Channels\FcmChannel;
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
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'sos_status_update',
            'sos_uuid'         => $this->sosRequest->uuid,
            'previous_status'  => $this->previousStatus,
            'new_status'       => $this->sosRequest->status,
            'resolution_notes' => $this->sosRequest->resolution_notes,
            'updated_at'       => $this->sosRequest->updated_at?->toIso8601String(),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $bodies = [
            'sos_accepted'    => 'A vet has accepted your emergency request.',
            'vet_on_the_way'  => 'The vet is on the way to you.',
            'arrived'         => 'The vet has arrived.',
            'sos_in_progress' => 'Your emergency is being handled.',
            'sos_completed'   => 'Your SOS request has been resolved.',
            'sos_cancelled'   => 'Your SOS request was cancelled.',
            'expired'         => 'Your SOS request expired with no vet available.',
        ];

        return [
            'title' => 'SOS Update',
            'body'  => $bodies[$this->sosRequest->status] ?? 'Your SOS status has changed.',
            'data'  => [
                'type'       => 'sos_status_update',
                'sos_uuid'   => $this->sosRequest->uuid,
                'new_status' => $this->sosRequest->status,
            ],
        ];
    }
}
