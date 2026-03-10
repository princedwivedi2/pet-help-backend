<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SosRequest;
use App\Models\VetProfile;
use App\Services\VisitService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitRecordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VisitService $visitService
    ) {}

    /**
     * Create a visit record for an appointment.
     * POST /api/v1/visit-records
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'recordable_type' => 'required|in:appointment,sos',
            'recordable_uuid' => 'required|string',
            'visit_notes'     => 'nullable|string|max:5000',
            'diagnosis'       => 'nullable|string|max:2000',
            'treatment_cost_breakdown' => 'nullable|array',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date'    => 'nullable|date',
            'follow_up_notes'   => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->forbidden('Only vets can create visit records.');
        }

        // Resolve the entity
        if ($request->recordable_type === 'appointment') {
            $record = Appointment::where('uuid', $request->recordable_uuid)->first();
            if (!$record || $record->vet_profile_id !== $vetProfile->id) {
                return $this->notFound('Appointment not found or not yours.');
            }
            $petId = $record->pet_id;
        } else {
            $record = SosRequest::where('uuid', $request->recordable_uuid)->first();
            if (!$record || $record->vet_profile_id !== $vetProfile->id) {
                return $this->notFound('SOS request not found or not yours.');
            }
            $petId = $record->pet_id;
        }

        try {
            $visitRecord = $this->visitService->create(array_merge(
                [
                    'appointment_id' => $request->recordable_type === 'appointment' ? $record->id : null,
                    'sos_request_id' => $request->recordable_type === 'sos' ? $record->id : null,
                    'vet_profile_id' => $vetProfile->id,
                    'user_id' => $record->user_id,
                    'pet_id' => $petId,
                ],
                $request->only([
                    'visit_notes', 'diagnosis', 'treatment_cost_breakdown',
                    'follow_up_required', 'follow_up_date', 'follow_up_notes',
                ])
            ));

            return $this->created('Visit record created', ['visit_record' => $visitRecord]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Update a visit record.
     * PUT /api/v1/visit-records/{uuid}
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'visit_notes'     => 'nullable|string|max:5000',
            'diagnosis'       => 'nullable|string|max:2000',
            'treatment_cost_breakdown' => 'nullable|array',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date'    => 'nullable|date',
            'follow_up_notes'   => 'nullable|string|max:2000',
        ]);

        $visitRecord = \App\Models\VisitRecord::where('uuid', $uuid)->first();

        if (!$visitRecord) {
            return $this->notFound('Visit record not found');
        }

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile || $visitRecord->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('You can only update your own visit records.');
        }

        try {
            $visitRecord = $this->visitService->update(
                $visitRecord,
                $request->only([
                    'visit_notes', 'diagnosis', 'treatment_cost_breakdown',
                    'follow_up_required', 'follow_up_date', 'follow_up_notes',
                ])
            );

            return $this->success('Visit record updated', ['visit_record' => $visitRecord]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Upload prescription for a visit record.
     * POST /api/v1/visit-records/{uuid}/prescription
     */
    public function uploadPrescription(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'prescription' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $visitRecord = \App\Models\VisitRecord::where('uuid', $uuid)->first();

        if (!$visitRecord) {
            return $this->notFound('Visit record not found');
        }

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile || $visitRecord->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('You can only upload for your own visit records.');
        }

        try {
            $visitRecord = $this->visitService->uploadPrescription(
                $visitRecord,
                $request->file('prescription')
            );

            return $this->success('Prescription uploaded', ['visit_record' => $visitRecord]);
        } catch (\Throwable $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Upload before/after images for a visit record.
     * POST /api/v1/visit-records/{uuid}/images
     */
    public function uploadImages(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'images'   => 'required|array|min:1|max:10',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'type'     => 'nullable|in:before,after',
        ]);

        $visitRecord = \App\Models\VisitRecord::where('uuid', $uuid)->first();

        if (!$visitRecord) {
            return $this->notFound('Visit record not found');
        }

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile || $visitRecord->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('You can only upload for your own visit records.');
        }

        try {
            $type = $request->type ?? 'before';
            $visitRecord = $this->visitService->uploadImages(
                $visitRecord,
                $request->file('images'),
                $type
            );

            return $this->success('Images uploaded', ['visit_record' => $visitRecord]);
        } catch (\Throwable $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get visit record for an appointment.
     * GET /api/v1/visit-records/appointment/{uuid}
     */
    public function forAppointment(string $uuid): JsonResponse
    {
        $appointment = Appointment::where('uuid', $uuid)->first();

        if (!$appointment) {
            return $this->notFound('Appointment not found');
        }

        $user = auth()->user();
        if ($appointment->user_id !== $user->id && !$user->isVet() && !$user->isAdmin()) {
            return $this->forbidden('Access denied');
        }

        $visitRecord = $this->visitService->getForAppointment($appointment->id);

        return $this->success(
            $visitRecord ? 'Visit record retrieved' : 'No visit record found',
            ['visit_record' => $visitRecord]
        );
    }

    /**
     * Get visit record for an SOS.
     * GET /api/v1/visit-records/sos/{uuid}
     */
    public function forSos(string $uuid): JsonResponse
    {
        $sos = SosRequest::where('uuid', $uuid)->first();

        if (!$sos) {
            return $this->notFound('SOS request not found');
        }

        $user = auth()->user();
        if ($sos->user_id !== $user->id && !$user->isVet() && !$user->isAdmin()) {
            return $this->forbidden('Access denied');
        }

        $visitRecord = $this->visitService->getForSos($sos->id);

        return $this->success(
            $visitRecord ? 'Visit record retrieved' : 'No visit record found',
            ['visit_record' => $visitRecord]
        );
    }
}
