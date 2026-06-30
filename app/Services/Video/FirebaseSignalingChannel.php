<?php

namespace App\Services\Video;

use App\Contracts\SignalingChannel;
use Kreait\Firebase\Contract\Database;

/**
 * Firebase Realtime Database implementation of SignalingChannel.
 *
 * Each room is a JSON object at "/signaling/room-{uuid}" with sub-paths for
 * offers, answers, ice_candidates that clients write under during peer setup.
 */
class FirebaseSignalingChannel implements SignalingChannel
{
    public function __construct(private Database $database) {}

    public function initializeRoom(string $path, array $metadata): void
    {
        $this->database->getReference($path)->set($metadata);
    }

    public function tearDownRoom(string $path): void
    {
        $ref = $this->database->getReference($path);
        $ref->update(['status' => 'closed']);
        $ref->remove();
    }
}
