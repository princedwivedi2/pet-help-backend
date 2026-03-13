<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\VetApplyRequest;
use App\Http\Requests\Api\V1\Vet\VetRegisterRequest;
use App\Http\Requests\Api\V1\Vet\VetUpdateProfileRequest;
use App\Models\VetAvailability;
use App\Models\VetProfile;
use App\Services\VetProfileCompletionService;
use App\Services\VetOnboardingService;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VetOnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetOnboardingService $vetOnboardingService,
        private VetProfileCompletionService $vetProfileCompletionService
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
        $profilePhoto = $request->file('profile_photo');

        try {
            $result = $this->vetOnboardingService->apply($data, $files, $profilePhoto);
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
            'profile_status' => $this->vetProfileCompletionService->buildCompletionPayload($vetProfile),
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

    /**
     * Update the authenticated vet's profile.
     *
     * PUT /api/v1/vet/profile
     */
    public function updateProfile(VetUpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $payload = $request->validated();

        $vetProfile->update([
            'clinic_name' => $payload['clinic_name'],
            'vet_name' => $payload['vet_name'],
            'phone' => $payload['phone'],
            'profile_photo' => $payload['profile_photo'],
            'address' => $payload['clinic_address'],
            'city' => $payload['city'] ?? $vetProfile->city,
            'state' => $payload['state'] ?? $vetProfile->state,
            'postal_code' => $payload['postal_code'] ?? $vetProfile->postal_code,
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'qualifications' => $payload['qualification'],
            'license_number' => $payload['license_number'],
            'specialization' => $payload['specialization'] ?? $vetProfile->specialization,
            'services' => $payload['services'],
            'accepted_species' => $payload['accepted_species'],
            'working_hours' => $payload['working_hours'],
            'consultation_fee' => $payload['consultation_fee'],
            'home_visit_fee' => $payload['home_visit_fee'] ?? null,
            'verification_documents' => $payload['verification_documents'] ?? null,
            'is_emergency_available' => $payload['is_emergency_available'] ?? $vetProfile->is_emergency_available,
            'is_24_hours' => $payload['is_24_hours'] ?? $vetProfile->is_24_hours,
        ]);

        $patch = [];
        if ($request->filled('degree_certificate')) {
            $patch['degree_certificate_url'] = $request->input('degree_certificate');
        }
        if ($request->filled('government_id')) {
            $patch['government_id_url'] = $request->input('government_id');
        }
        if (!empty($patch)) {
            $vetProfile->update($patch);
        }

        return $this->success('Vet profile updated successfully', [
            'vet_profile' => $vetProfile->fresh(),
            'profile_status' => $this->vetProfileCompletionService->buildCompletionPayload($vetProfile->fresh()),
        ]);
    }

    /**
     * List vet availabilities.
     *
     * GET /api/v1/vet/availabilities
     */
    public function availabilities(Request $request): JsonResponse
    {
        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        return $this->success('Availabilities retrieved', [
            'availabilities' => $vetProfile->availabilities,
        ]);
    }

    /**
     * Create a vet availability slot.
     *
     * POST /api/v1/vet/availabilities
     */
    public function storeAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'day_of_week'        => 'required|integer|min:0|max:6',
            'open_time'          => 'required|date_format:H:i',
            'close_time'         => 'required|date_format:H:i|after:open_time',
            'is_emergency_hours' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $availability = VetAvailability::create([
            'vet_profile_id'    => $vetProfile->id,
            'day_of_week'       => $request->day_of_week,
            'open_time'         => $request->open_time,
            'close_time'        => $request->close_time,
            'is_emergency_hours' => $request->boolean('is_emergency_hours', false),
        ]);

        return $this->created('Availability created', ['availability' => $availability]);
    }

    /**
     * Update a vet availability slot.
     *
     * PUT /api/v1/vet/availabilities/{id}
     */
    public function updateAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'day_of_week'        => 'sometimes|integer|min:0|max:6',
            'open_time'          => 'sometimes|date_format:H:i',
            'close_time'         => 'sometimes|date_format:H:i',
            'is_emergency_hours' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $availability = VetAvailability::where('id', $id)
            ->where('vet_profile_id', $vetProfile->id)
            ->first();

        if (!$availability) {
            return $this->notFound('Availability not found.');
        }

        $availability->update($request->only([
            'day_of_week', 'open_time', 'close_time', 'is_emergency_hours',
        ]));

        return $this->success('Availability updated', ['availability' => $availability]);
    }

    /**
     * Delete a vet availability slot.
     *
     * DELETE /api/v1/vet/availabilities/{id}
     */
    public function destroyAvailability(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $availability = VetAvailability::where('id', $id)
            ->where('vet_profile_id', $vetProfile->id)
            ->first();

        if (!$availability) {
            return $this->notFound('Availability not found.');
        }

        $availability->delete();

        return $this->success('Availability deleted');
    }

    /**
     * Update the vet's availability status.
     *
     * PUT /api/v1/vet/status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'availability_status' => 'required|string|in:available,busy,offline,on_leave',
        ]);

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $vetProfile->update(['availability_status' => $request->availability_status]);

        return $this->success('Status updated', [
            'availability_status' => $vetProfile->availability_status,
        ]);
    }
}
