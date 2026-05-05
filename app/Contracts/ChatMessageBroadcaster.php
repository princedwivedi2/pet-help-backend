<?php

namespace App\Contracts;

use App\Models\ConsultationMessage;

/**
 * Pushes consultation chat messages to a real-time fan-out so connected clients
 * see new messages without polling.
 *
 * Source of truth stays in MySQL/SQLite (`consultation_messages` table). The
 * broadcaster is a side-effect — failures are logged but never block the
 * synchronous write.
 *
 * Implementations:
 *   - FirebaseChatBroadcaster — production, writes to Firestore under
 *     `consultations/{sessionUuid}/messages/{messageUuid}` so clients can subscribe
 *     to the per-session collection.
 *   - NullChatBroadcaster — used when Firebase is unavailable (graceful degrade).
 */
interface ChatMessageBroadcaster
{
    /**
     * Push a freshly-persisted message to the realtime channel for its session.
     */
    public function broadcastMessage(ConsultationMessage $message): void;
}
