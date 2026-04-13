<?php

namespace App\Events;

use App\Models\SosRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SosRequest $sosRequest,
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
            'uuid'            => $this->sosRequest->uuid,
            'status'          => $this->sosRequest->status,
            'assigned_vet_id' => $this->sosRequest->assigned_vet_id,
            'updated_at'      => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.status.changed';
    }
}
