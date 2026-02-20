<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\VetRegisterRequest;
use App\Services\VetOnboardingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class VetOnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetOnboardingService $vetOnboardingService
    ) {}

    /**
     * Register a new veterinarian.
     *
     * POST /api/v1/vet/register
     *
     * Creates a user account with role=vet and a linked vet profile.
     * The profile is unverified (is_verified = false) until admin approval.
     * A Sanctum token is issued for immediate authenticated access.
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Vet registered successfully. Your profile is pending verification.",
     *   "data": {
     *     "user": { "id": 1, "name": "Dr. Smith", "email": "dr@vet.com", "role": "vet" },
     *     "vet_profile": { "uuid": "...", "clinic_name": "...", "is_verified": false, ... },
     *     "token": "1|abc..."
     *   }
     * }
     */
    public function register(VetRegisterRequest $request): JsonResponse
    {
        $result = $this->vetOnboardingService->registerVet($request->validated());

        return $this->created('Vet registered successfully. Your profile is pending verification.', [
            'user'        => $result['user'],
            'vet_profile' => $result['vet_profile'],
            'token'       => $result['token'],
        ]);
    }

    /**
     * Get the authenticated vet's own profile.
     *
     * GET /api/v1/vet/profile
     */
    public function profile(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isVet()) {
            return $this->forbidden('Only veterinarians can access this resource.');
        }

        $vetProfile = \App\Models\VetProfile::where('user_id', $user->id)
            ->with('availabilities')
            ->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        return $this->success('Vet profile retrieved successfully', [
            'vet_profile' => $vetProfile,
        ]);
    }
}
