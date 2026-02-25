<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sos\StoreSosRequest;
use App\Http\Requests\Api\V1\Sos\UpdateSosStatusRequest;
use App\Models\SosRequest;
use App\Services\SosService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SosController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SosService $sosService
    ) {}

    public function store(StoreSosRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check rate limit: 5 SOS requests per hour
        $recentCount = $this->sosService->getUserSosCountLastHour($user);
        if ($recentCount >= 5) {
            Log::channel('stack')->warning('SOS rate limit hit', [
                'user_id' => $user->id,
                'count_last_hour' => $recentCount,
            ]);
            return $this->tooManyRequests('Rate limit exceeded', [
                'sos' => ['You can only create 5 SOS requests per hour. Please wait before creating another.'],
            ]);
        }

        // Check for active SOS
        $activeSos = $this->sosService->getActiveSosForUser($user);
        if ($activeSos) {
            return $this->validationError('Active SOS exists', [
                'sos' => ['You already have an active SOS request. Please complete or cancel it first.'],
            ]);
        }

        // Validate pet belongs to user
        if ($request->pet_id) {
            $pet = $user->pets()->find($request->pet_id);
            if (!$pet) {
                return $this->validationError('Pet not found', [
                    'pet_id' => ['Pet not found or does not belong to you.'],
                ]);
            }
        }

        $sosRequest = $this->sosService->createSos($user, $request->validated());

        // Find and notify nearby vets (stub) — non-blocking
        try {
            $vetNotification = $this->sosService->findNearestVetsStub(
                $request->latitude,
                $request->longitude
            );
        } catch (\Throwable $e) {
            report($e);
            $vetNotification = ['vets_notified' => 0, 'vets' => [], 'error' => 'Vet search temporarily unavailable'];
        }

        return $this->created('SOS request created successfully', [
            'sos' => $sosRequest,
            'notification' => $vetNotification,
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $activeSos = $this->sosService->getActiveSosForUser($request->user());

        return $this->success(
            $activeSos ? 'Active SOS retrieved successfully' : 'No active SOS request',
            ['sos' => $activeSos]
        );
    }

    public function updateStatus(UpdateSosStatusRequest $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        // Owner can cancel/complete; vets and admins can also update status
        $sosRequest = SosRequest::where('uuid', $uuid)->first();

        if (!$sosRequest) {
            return $this->notFound('SOS request not found');
        }

        $isOwner = $sosRequest->user_id === $user->id;
        $isVet   = $user->isVet();
        $isAdmin = $user->isAdmin();

        if (!$isOwner && !$isVet && !$isAdmin) {
            return $this->forbidden('You do not have permission to update this SOS request.');
        }

        $newStatus = $request->status;

        // Only owner or admin can cancel
        if ($newStatus === 'cancelled' && !$isOwner && !$isAdmin) {
            return $this->forbidden('Only the SOS owner or an admin can cancel the request.');
        }

        try {
            $sosRequest = $this->sosService->updateStatus(
                $sosRequest,
                $newStatus,
                $request->resolution_notes
            );
        } catch (\DomainException $e) {
            return $this->validationError($e->getMessage(), [
                'status' => [$e->getMessage()],
            ]);
        }

        return $this->success('SOS status updated successfully', ['sos' => $sosRequest]);
    }
}
