<?php

namespace App\Services\Chat;

use App\Contracts\ChatMessageBroadcaster;
use App\Models\ConsultationMessage;

/**
 * No-op broadcaster used when Firebase is unconfigured or in tests that don't
 * care about realtime fan-out. Source of truth (DB row) is unaffected.
 */
class NullChatBroadcaster implements ChatMessageBroadcaster
{
    public function broadcastMessage(ConsultationMessage $message): void
    {
        // intentionally empty
    }
}
