<?php

namespace App\Services\Video;

use App\Contracts\VideoProviderInterface;
use App\Models\ConsultationSession;

/**
 * No-op video provider. Returns synthetic room ids so the consultation flow
 * works end-to-end for chat-only consults and local development.
 *
 * Replace with a real provider (Twilio/Daily/Agora/LiveKit/Jitsi) before video
 * or audio consults are usable.
 */
class NullVideoProvider implements VideoProviderInterface
{
    public function createRoom(ConsultationSession $session): array
    {
        return [
            'room_id' => 'null-room-' . $session->uuid,
            'provider' => $this->name(),
            'metadata' => ['warning' => 'NullVideoProvider — no real video transport. Wire a real provider in AppServiceProvider.'],
        ];
    }

    public function generateJoinToken(ConsultationSession $session, string $role, int $userId): string
    {
        // Non-functional placeholder. A real provider returns a JWT signed with its credentials.
        return 'null-token-' . $session->uuid . '-' . $role . '-' . $userId;
    }

    public function destroyRoom(ConsultationSession $session): void
    {
        // No-op
    }

    public function name(): string
    {
        return 'null';
    }
}
