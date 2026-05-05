<?php

namespace App\Services\Video;

use App\Contracts\SignalingChannel;
use App\Contracts\VideoProviderInterface;
use App\Models\ConsultationSession;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WebRTC provider using browser P2P with a pluggable signaling channel.
 *
 * Production wires this with FirebaseSignalingChannel (Realtime Database).
 * Tests can swap in any SignalingChannel mock.
 *
 * Client-side responsibilities:
 * - Browser WebRTC (navigator.mediaDevices.getUserMedia, RTCPeerConnection)
 * - Subscribe to the signaling path returned in metadata for SDP/ICE exchange
 * - Tear down RTCPeerConnections when the room is destroyed
 *
 * The constructor takes a Closure that lazily returns a SignalingChannel (or null
 * when signaling transport is unavailable). This indirection keeps construction
 * cheap, lets the binding fail open, and makes testing trivial.
 */
class WebRtcProvider implements VideoProviderInterface
{
    /** @var Closure(): ?SignalingChannel */
    private Closure $channelResolver;

    private ?SignalingChannel $channel = null;
    private bool $channelResolved = false;

    /**
     * @param  Closure(): ?SignalingChannel  $channelResolver
     */
    public function __construct(Closure $channelResolver)
    {
        $this->channelResolver = $channelResolver;
    }

    private function channel(): ?SignalingChannel
    {
        if (!$this->channelResolved) {
            try {
                $this->channel = ($this->channelResolver)();
            } catch (\Throwable $e) {
                Log::warning('WebRTC signaling resolver failed', ['error' => $e->getMessage()]);
                $this->channel = null;
            }
            $this->channelResolved = true;
        }
        return $this->channel;
    }

    public function createRoom(ConsultationSession $session): array
    {
        $roomId = 'room-' . $session->uuid;
        $signalingPath = "/signaling/{$roomId}";

        $channel = $this->channel();
        if ($channel === null) {
            return [
                'room_id' => $roomId,
                'provider' => $this->name(),
                'metadata' => [
                    'signaling_path' => $signalingPath,
                    'fallback' => true,
                    'warning' => 'Signaling transport unavailable — P2P only, no ICE candidate exchange',
                ],
            ];
        }

        try {
            $channel->initializeRoom($signalingPath, [
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
                    'ttl_seconds' => 3600,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to create WebRTC room', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'room_id' => $roomId,
                'provider' => $this->name(),
                'metadata' => [
                    'signaling_path' => $signalingPath,
                    'fallback' => true,
                    'warning' => 'Signaling transport error — P2P only, no ICE candidate exchange',
                ],
            ];
        }
    }

    /**
     * Generate a signed join token for a participant.
     * Token format: `header.payload.signature` (JWT-like, HMAC-SHA256, app key).
     * Client uses token to authenticate with the signaling channel.
     */
    public function generateJoinToken(ConsultationSession $session, string $role, int $userId): string
    {
        $payload = [
            'room_id' => 'room-' . $session->uuid,
            'session_id' => $session->id,
            'user_id' => $userId,
            'role' => $role,
            'iat' => now()->unix(),
            'exp' => now()->addHour()->unix(),
            'nonce' => Str::random(32),
        ];

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$header}.{$body}", config('app.key'), true);

        return "{$header}.{$body}." . base64_encode($signature);
    }

    public function destroyRoom(ConsultationSession $session): void
    {
        $signalingPath = '/signaling/room-' . $session->uuid;
        $channel = $this->channel();
        if ($channel === null) {
            return;
        }

        try {
            $channel->tearDownRoom($signalingPath);
            Log::info('WebRTC room destroyed', ['session_id' => $session->id]);
        } catch (\Throwable $e) {
            Log::warning('Failed to destroy WebRTC room', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function name(): string
    {
        return 'webrtc';
    }
}
