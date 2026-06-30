<?php

namespace App\Contracts;

use App\Models\ConsultationSession;

/**
 * Adapter for a video/audio room provider (Twilio Video, Daily.co, Agora, LiveKit, Jitsi…).
 *
 * Pick ONE provider, write an implementation, bind it in AppServiceProvider:
 *
 *   $this->app->bind(VideoProviderInterface::class, TwilioVideoProvider::class);
 *
 * The default binding is NullVideoProvider — every method returns a marker that
 * signals "no provider configured" so the rest of the consultation flow still
 * functions for development and chat-only consults.
 */
interface VideoProviderInterface
{
    /**
     * Create a room for the given session. Returns provider-specific room id and
     * any metadata to persist (region, recording id, etc.).
     *
     * @return array{room_id: string, provider: string, metadata?: array}
     */
    public function createRoom(ConsultationSession $session): array;

    /**
     * Mint a short-lived join token for a participant.
     *
     * @param  string  $role  'user' | 'vet'
     */
    public function generateJoinToken(ConsultationSession $session, string $role, int $userId): string;

    /**
     * Tear down the room. Idempotent.
     */
    public function destroyRoom(ConsultationSession $session): void;

    /**
     * Provider name (e.g. "twilio", "daily", "agora"). Must match the value
     * stored in consultation_sessions.room_provider for sanity-checking.
     */
    public function name(): string;
}
