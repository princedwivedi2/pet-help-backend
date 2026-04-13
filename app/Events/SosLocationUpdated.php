<?php

namespace App\Events;

use App\Models\SosRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SosRequest $sosRequest,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sos.' . $this->sosRequest->uuid),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'uuid'       => $this->sosRequest->uuid,
            'latitude'   => $this->latitude,
            'longitude'  => $this->longitude,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.location.updated';
    }
}
