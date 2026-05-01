<?php

namespace App\Jobs;

use App\Models\SosRequest;
use App\Services\SosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSosNearbyVetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $sosRequestId)
    {
    }

    public function handle(SosService $sosService): void
    {
        $sosRequest = SosRequest::find($this->sosRequestId);
        if (!$sosRequest) {
            return;
        }

        $sosService->findNearestVets(
            (float) $sosRequest->latitude,
            (float) $sosRequest->longitude,
            $sosRequest
        );
    }
}
