<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\VetApplyRequest;
use App\Http\Requests\Api\V1\Vet\VetRegisterRequest;
use App\Models\VetProfile;
use App\Services\VetOnboardingService;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VetOnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetOnboardingService $vetOnboardingService
    ) {}

    /**
     * Submit a vet application.
     *
     * POST /api/v1/vet/apply
     *
     * Creates User (role=vet) + VetProfile (vet_status=pending) with optional document uploads.
     * Password hashing relies on model cast only.
     */
    public function apply(VetApplyRequest $request): JsonResponse
    {
        $data  = $request->validated();
        $files = $request->file('documents', []);

        try {
            $result = $this->vetOnboardingService->apply($data, $files);
        } catch (QueryException $e) {
            report($e);
            return $this->error('Registration failed. This email or license number may already be in use.', null, 409);
        }

        return $this->created('Vet application submitted successfully. Your profile is under review.', [
            'user'        => $result['user'],
            'vet_profile' => $result['vet_profile'],
            'documents'   => $result['documents'],
        ]);
    }

    /**
     * Legacy registration endpoint — delegates to apply.
     *
     * POST /api/v1/vet/register
     */
    public function register(VetRegisterRequest $request): JsonResponse
    {
        $data  = $request->validated();
        $files = [];

        try {
            $result = $this->vetOnboardingService->apply($data, $files);
        } catch (QueryException $e) {
            report($e);
            return $this->error('Registration failed. This email or license number may already be in use.', null, 409);
        }

        return $this->created('Vet registered successfully. Your profile is pending verification.', [
            'user'        => $result['user'],
            'vet_profile' => $result['vet_profile'],
        ]);
    }

    /**
     * Get the authenticated vet's own profile.
     *
     * GET /api/v1/vet/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        $vetProfile = VetProfile::where('user_id', $user->id)
            ->with('availabilities')
            ->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        return $this->success('Vet profile retrieved successfully', [
            'vet_profile' => $vetProfile,
        ]);
    }

    /**
     * Upload a verification document.
     *
     * POST /api/v1/vet/documents
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|string|in:license,degree,id_proof,clinic_registration',
        ]);

        $user = $request->user();

        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        try {
            $path = $this->vetOnboardingService->uploadDocument(
                $vetProfile,
                $request->file('document'),
                $request->document_type
            );
        } catch (\Throwable $e) {
            report($e);
            return $this->error('Document upload failed. Please try again.', null, 500);
        }

        return $this->success('Document uploaded successfully', [
            'document_path' => $path,
            'document_type' => $request->document_type,
        ]);
    }
}
