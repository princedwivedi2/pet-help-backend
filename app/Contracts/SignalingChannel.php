<?php

namespace App\Contracts;

/**
 * Realtime signaling transport for WebRTC peer-connection setup.
 *
 * Each room has a unique path (e.g. "/signaling/room-{uuid}"). Peers exchange
 * SDP offers/answers + ICE candidates by reading/writing under that path.
 *
 * Implementations:
 *   - FirebaseSignalingChannel — production, uses Firebase Realtime Database
 *   - tests provide their own Mockery mock of this interface
 *
 * The provider owns the path; the channel only knows how to write room metadata
 * and tear it down. The actual SDP/ICE exchange happens client-side via the
 * Firebase JS SDK subscribed to the same path.
 */
interface SignalingChannel
{
    /**
     * Initialize the room with metadata. Overwrites any prior contents at $path.
     */
    public function initializeRoom(string $path, array $metadata): void;

    /**
     * Tear down the room. Idempotent — safe to call when the room doesn't exist.
     */
    public function tearDownRoom(string $path): void;
}
