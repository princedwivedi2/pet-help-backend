<?php

namespace App\Services\Chat;

use App\Contracts\ChatMessageBroadcaster;
use App\Contracts\FirestoreWriter;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Pushes consultation chat messages to Firestore for realtime fan-out.
 *
 * Document layout: `consultations/{sessionUuid}/messages/{messageId}` so clients
 * can subscribe with `firestore.collection('consultations/{uuid}/messages')`
 * and order by `created_at` for chronological history.
 *
 * Source of truth stays in MySQL (`consultation_messages`). Firestore writes
 * are best-effort — any failure (network, no gRPC extension, missing creds) is
 * logged but never propagated; chat persistence MUST not depend on Firestore.
 *
 * Constructor takes a Closure that lazily resolves a FirestoreWriter (or null
 * when Firestore is unavailable). Same pattern as the Realtime DB broadcaster.
 */
class FirestoreChatBroadcaster implements ChatMessageBroadcaster
{
    /** @var Closure(): ?FirestoreWriter */
    private Closure $writerResolver;

    private ?FirestoreWriter $writer = null;
    private bool $resolved = false;

    /**
     * @param  Closure(): ?FirestoreWriter  $writerResolver
     */
    public function __construct(Closure $writerResolver)
    {
        $this->writerResolver = $writerResolver;
    }

    private function writer(): ?FirestoreWriter
    {
        if (!$this->resolved) {
            try {
                $this->writer = ($this->writerResolver)();
            } catch (\Throwable $e) {
                Log::warning('Firestore writer resolver failed', ['error' => $e->getMessage()]);
                $this->writer = null;
            }
            $this->resolved = true;
        }
        return $this->writer;
    }

    public function broadcastMessage(ConsultationMessage $message): void
    {
        $writer = $this->writer();
        if ($writer === null) {
            return;
        }

        $session = ConsultationSession::find($message->consultation_session_id);
        if (!$session) {
            return;
        }

        try {
            $writer->setDocument(
                "consultations/{$session->uuid}/messages/{$message->id}",
                [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_role' => $message->sender_role,
                    'type' => $message->type,
                    'body' => $message->body,
                    'attachment_path' => $message->attachment_path,
                    'created_at' => optional($message->created_at)->toIso8601String() ?? now()->toIso8601String(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Firestore chat broadcast failed', [
                'message_id' => $message->id,
                'session_id' => $message->consultation_session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
