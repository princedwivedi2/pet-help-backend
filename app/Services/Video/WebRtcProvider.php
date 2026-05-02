<?php

namespace App\Services\Video;

use App\Contracts\VideoProviderInterface;
use App\Models\ConsultationSession;
use Kreait\Firebase\Factory as FirebaseFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * WebRTC provider using browser P2P with Firebase Realtime Database for signaling.
 *
 * For each consultation session:
 * - A room is created with a unique room_id
 * - Participants exchange SDP offers/answers and ICE candidates via Firebase
 * - Join tokens are signed with a secret and include participant metadata
 * - Rooms auto-expire after consultation ends or a TTL passes
 *
 * Client must handle:
 * - Browser WebRTC (navigator.mediaDevices.getUserMedia, RTCPeerConnection)
 * - Firebase Realtime Database listener for signaling (ICE candidates, SDP)
 * - Audio/video stream setup and tear-down
 */
class WebRtcProvider implements VideoProviderInterface
{
    private ?object $database = null;

    public function __construct(private FirebaseFactory $firebaseFactory)
    {
    }

    /**
     * Get Firebase Realtime Database reference for signaling.
     */
    private function getDatabase(): object
    {
        if ($this->database === null) {
            $this->database = $this->firebaseFactory->createDatabase();
        }
        return $this->database;
    }

    /**
     * Create a room for the consultation session.
     * Initializes Firebase signaling structure and returns room metadata.
     */
    public function createRoom(ConsultationSession $session): array
    {
        $roomId = 'room-' . $session->uuid;
        $signalingPath = "/signaling/{$roomId}";

        try {
            $database = $this->getDatabase();
            $roomRef = $database->getReference($signalingPath);

            // Initialize room structure with metadata
            $roomRef->set([
                'created_at' => now()->toIso8601String(),
                'session_id' => $session->id,
                'participants' => [],
                'offers' => [],
                'answers' => [],
                'ice_candidates' => [],
                'status' => 'active',
            ]);

            Log::info('WebRTC room created', [
                'room_id' => $roomId,
                'session_id' => $session->id,
            ]);

            return [
                'room_id' => $roomId,
                'provider' => $this->name(),
                'metadata' => [
                    'signaling_path' => $signalingPath,
                    'created_at' => now()->toIso8601String(),
                    'ttl_seconds' => 3600, // 1 hour session lifetime
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to create WebRTC room', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            // Graceful fallback: return room info without Firebase (P2P only)
            return [
                'room_id' => $roomId,
                'provider' => $this->name(),
                'metadata' => [
                    'signaling_path' => $signalingPath,
                    'fallback' => true,
                    'warning' => 'Firebase unavailable — P2P only, no ICE candidate exchange',
                ],
            ];
        }
    }

    /**
     * Generate a signed join token for a participant.
     * Token includes room_id, user_id, role, and expiration.
     * Client uses token to authenticate with Firebase signaling.
     */
    public function generateJoinToken(ConsultationSession $session, string $role, int $userId): string
    {
        $payload = [
            'room_id' => 'room-' . $session->uuid,
            'session_id' => $session->id,
            'user_id' => $userId,
            'role' => $role, // 'user' or 'vet'
            'iat' => now()->unix(),
            'exp' => now()->addHour()->unix(), // Token valid for 1 hour
            'nonce' => Str::random(32),
        ];

        // Simple JWT-like token (Base64 encoded JSON + HMAC signature)
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = base64_encode(json_encode($payload));
        $signature = hash_hmac(
            'sha256',
            "{$header}.{$body}",
            config('app.key'),
            true
        );
        $signatureB64 = base64_encode($signature);

        $token = "{$header}.{$body}.{$signatureB64}";

        Log::debug('WebRTC join token generated', [
            'room_id' => $payload['room_id'],
            'user_id' => $userId,
            'role' => $role,
        ]);

        return $token;
    }

    /**
     * Tear down the room by clearing Firebase signaling data.
     * Client should also close local RTCPeerConnections.
     */
    public function destroyRoom(ConsultationSession $session): void
    {
        $roomId = 'room-' . $session->uuid;
        $signalingPath = "/signaling/{$roomId}";

        try {
            $database = $this->getDatabase();
            $roomRef = $database->getReference($signalingPath);

            // Mark as inactive then delete
            $roomRef->update(['status' => 'closed']);
            $roomRef->remove();

            Log::info('WebRTC room destroyed', [
                'room_id' => $roomId,
                'session_id' => $session->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to destroy WebRTC room', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            // Non-fatal — Firebase may be momentarily unavailable
        }
    }

    /**
     * Provider name.
     */
    public function name(): string
    {
        return 'webrtc';
    }
}
