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

class SosController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SosService $sosService
    ) {}

    public function store(StoreSosRequest $request): JsonResponse
    {
        $user = $request->user();

        // Validate pet belongs to user
        if ($request->pet_id) {
            $pet = $user->pets()->find($request->pet_id);
            if (!$pet) {
                return $this->validationError('Pet not found', [
                    'pet_id' => ['Pet not found or does not belong to you.'],
                ]);
            }
        }

        try {
            $sosRequest = $this->sosService->createSos($user, $request->validated());
        } catch (\DomainException $e) {
            return $this->validationError($e->getMessage(), [
                'sos' => [$e->getMessage()],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
            return $this->error('Unable to create SOS request. Please try again.', null, 409);
        }

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
        $user = $request->user();

        // Vets and admins see ALL active SOS; users see only their own
        if ($user->isVet() || $user->isAdmin()) {
            $activeSos = $this->sosService->getAllActiveSos();

            return $this->success(
                $activeSos->isEmpty() ? 'No active SOS requests' : 'Active SOS requests retrieved',
                ['sos_requests' => $activeSos]
            );
        }

        $activeSos = $this->sosService->getActiveSosForUser($user);

        return $this->success(
            $activeSos ? 'Active SOS retrieved successfully' : 'No active SOS request',
            ['sos' => $activeSos]
        );
    }

    public function updateStatus(UpdateSosStatusRequest $request, string $uuid): JsonResponse
    {
        $user = $request->user();

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
        if (in_array($newStatus, ['cancelled', 'sos_cancelled']) && !$isOwner && !$isAdmin) {
            return $this->forbidden('Only the SOS owner or an admin can cancel the request.');
        }

        try {
            // Build extra data (vet location, charges etc.)
            $extra = [];
            if ($request->vet_latitude)  $extra['vet_latitude'] = (float) $request->vet_latitude;
            if ($request->vet_longitude) $extra['vet_longitude'] = (float) $request->vet_longitude;
            if ($request->emergency_charge) $extra['emergency_charge'] = (float) $request->emergency_charge;
            if ($request->distance_travelled_km) $extra['distance_travelled_km'] = (float) $request->distance_travelled_km;

            // If vet is accepting, attach their profile
            if (in_array($newStatus, ['sos_accepted', 'acknowledged']) && $isVet) {
                $vetProfile = \App\Models\VetProfile::where('user_id', $user->id)->first();
                if ($vetProfile) {
                    $extra['vet_profile_id'] = $vetProfile->id;
                }
            }

            $sosRequest = $this->sosService->updateStatus(
                $sosRequest,
                $newStatus,
                $request->resolution_notes,
                $extra
            );
        } catch (\DomainException $e) {
            return $this->validationError($e->getMessage(), [
                'status' => [$e->getMessage()],
            ]);
        }

        return $this->success('SOS status updated successfully', ['sos' => $sosRequest]);
    }

    /**
     * Update vet location for live tracking during SOS.
     * PUT /api/v1/sos/{uuid}/location
     */
    public function updateLocation(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $sosRequest = SosRequest::where('uuid', $uuid)->first();

        if (!$sosRequest) {
            return $this->notFound('SOS request not found');
        }

        $user = $request->user();
        if (!$user->isVet() && !$user->isAdmin()) {
            return $this->forbidden('Only vets can update location.');
        }

        $sosRequest = $this->sosService->updateVetLocation(
            $sosRequest,
            (float) $request->latitude,
            (float) $request->longitude
        );

        return $this->success('Location updated', [
            'sos' => $sosRequest,
        ]);
    }
}
