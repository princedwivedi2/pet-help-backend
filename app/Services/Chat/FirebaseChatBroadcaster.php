<?php

namespace App\Services\Chat;

use App\Contracts\ChatMessageBroadcaster;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use Closure;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;

/**
 * Pushes consultation chat messages to Firebase Realtime Database so subscribed
 * clients see them in real-time.
 *
 * Path layout: `/consultations/{sessionUuid}/messages/{messageUuid}` — clients
 * `.on('child_added')` to that path to receive new messages.
 *
 * Constructor takes a Closure that lazily resolves the Database (or null when
 * Firebase is unavailable). Same pattern as WebRtcProvider so we don't depend
 * on the final `FirebaseFactory` class and tests can mock the resolver.
 */
class FirebaseChatBroadcaster implements ChatMessageBroadcaster
{
    /** @var Closure(): ?Database */
    private Closure $databaseResolver;

    private ?Database $database = null;
    private bool $resolved = false;

    /**
     * @param  Closure(): ?Database  $databaseResolver
     */
    public function __construct(Closure $databaseResolver)
    {
        $this->databaseResolver = $databaseResolver;
    }

    private function database(): ?Database
    {
        if (!$this->resolved) {
            try {
                $this->database = ($this->databaseResolver)();
            } catch (\Throwable $e) {
                Log::warning('Chat broadcaster database resolver failed', ['error' => $e->getMessage()]);
                $this->database = null;
            }
            $this->resolved = true;
        }
        return $this->database;
    }

    public function broadcastMessage(ConsultationMessage $message): void
    {
        $database = $this->database();
        if ($database === null) {
            return;
        }

        $session = ConsultationSession::find($message->consultation_session_id);
        if (!$session) {
            return;
        }

        try {
            $path = "/consultations/{$session->uuid}/messages/{$message->id}";
            $database->getReference($path)->set([
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_role' => $message->sender_role,
                'type' => $message->type,
                'body' => $message->body,
                'attachment_path' => $message->attachment_path,
                'created_at' => optional($message->created_at)->toIso8601String() ?? now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Source of truth is MySQL — do NOT throw, just log.
            Log::warning('Chat broadcast failed', [
                'message_id' => $message->id,
                'session_id' => $message->consultation_session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
