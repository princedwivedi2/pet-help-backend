<?php

namespace App\Notifications;

use App\Models\SosRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private SosRequest $sosRequest,
        private bool $isEscalation = false
    ) {}

    /**
     * Delivery channels.
     *
     * Currently: database only (in-app notifications).
     * TODO: Add Laravel Broadcasting for real-time WebSocket push.
     * TODO: Add FCM for mobile push notifications.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        // For now: database only
        // Future: return ['database', 'broadcast', FcmChannel::class];
        return ['database'];
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Calculate urgency based on time elapsed and emergency type.
     */
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
