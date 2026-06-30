<?php

namespace App\Notifications;

use App\Models\SosRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private SosRequest $sosRequest,
        private bool $isEscalation = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->isEscalation
            ? '🚨 URGENT: Escalated SOS - No vet has responded!'
            : '🚨 New SOS Emergency Request';

        return [
            'type' => 'sos_alert',
            'title' => $title,
            'sos_uuid' => $this->sosRequest->uuid,
            'emergency_type' => $this->sosRequest->emergency_type,
            'description' => $this->sosRequest->description,
            'latitude' => $this->sosRequest->latitude,
            'longitude' => $this->sosRequest->longitude,
            'address' => $this->sosRequest->address,
            'user_name' => $this->sosRequest->user?->name ?? 'Unknown',
            'user_phone' => $this->sosRequest->user?->phone,
            'pet_name' => $this->sosRequest->pet?->name,
            'pet_species' => $this->sosRequest->pet?->species,
            'is_escalation' => $this->isEscalation,
            'escalation_level' => $this->sosRequest->escalation_level ?? 0,
            'created_at' => $this->sosRequest->created_at?->toIso8601String(),
            'urgency' => $this->calculateUrgency(),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = $this->isEscalation
            ? 'URGENT: Escalated SOS'
            : 'New SOS Emergency Request';

        $petName = $this->sosRequest->pet?->name;
        $body = $petName
            ? "Emergency for {$petName}: {$this->sosRequest->emergency_type}"
            : "Emergency: {$this->sosRequest->emergency_type}";

        return [
            'title' => $title,
            'body'  => $body,
            'data'  => [
                'type'           => 'sos_alert',
                'sos_uuid'       => $this->sosRequest->uuid,
                'emergency_type' => $this->sosRequest->emergency_type,
                'is_escalation'  => $this->isEscalation ? 'true' : 'false',
            ],
        ];
    }

    private function calculateUrgency(): string
    {
        $minutesElapsed = $this->sosRequest->created_at?->diffInMinutes(now()) ?? 0;
        $criticalTypes = ['breathing', 'seizure', 'poisoning', 'accident'];

        if (in_array($this->sosRequest->emergency_type, $criticalTypes) || $minutesElapsed > 15) {
            return 'critical';
        }

        if ($this->isEscalation || $minutesElapsed > 10) {
            return 'high';
        }

        return 'normal';
    }
}
