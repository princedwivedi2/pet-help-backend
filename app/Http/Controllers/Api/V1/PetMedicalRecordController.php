<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pet\StorePetMedicalRecordRequest;
use App\Http\Requests\Api\V1\Pet\UpdatePetMedicalRecordRequest;
use App\Models\PetMedicalRecord;
use App\Services\PetMedicalRecordService;
use App\Services\PetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetMedicalRecordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PetService $petService,
        private PetMedicalRecordService $medicalRecordService,
    ) {}

    /**
     * List all medical records for a pet.
     * GET /api/v1/pets/{petId}/medical-records
     *
     * Query params:
     *   - type: diagnosis|vaccination|medicine|lab_report|general
     *   - from: YYYY-MM-DD (filter by recorded_at >=)
     *   - to:   YYYY-MM-DD (filter by recorded_at <=)
     *   - per_page: int (max 50)
     */
    public function index(Request $request, int $petId): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);

        $records = $this->medicalRecordService->getRecordsForPet(
            $pet,
            $request->query('type'),
            $request->query('from'),
            $request->query('to'),
            $perPage
        );

        return $this->success('Medical records retrieved successfully', [
            'pet_id' => $pet->id,
            'records' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Create a new medical record for a pet.
     * POST /api/v1/pets/{petId}/medical-records
     */
    public function store(StorePetMedicalRecordRequest $request, int $petId): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $record = $this->medicalRecordService->createRecord(
            $pet,
            $request->user(),
            $request->validated()
        );

        return $this->created('Medical record created successfully', [
            'record' => $record,
        ]);
    }

    /**
     * Show a single medical record.
     * GET /api/v1/pets/{petId}/medical-records/{uuid}
     */
    public function show(Request $request, int $petId, string $uuid): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $record = $this->medicalRecordService->findRecordForPet($pet, $uuid);

        if (!$record) {
            return $this->notFound('Medical record not found');
        }

        return $this->success('Medical record retrieved successfully', [
            'record' => $record,
        ]);
    }

    /**
     * Update a medical record.
     * PUT /api/v1/pets/{petId}/medical-records/{uuid}
     */
    public function update(UpdatePetMedicalRecordRequest $request, int $petId, string $uuid): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $record = $this->medicalRecordService->findRecordForPet($pet, $uuid);

        if (!$record) {
            return $this->notFound('Medical record not found');
        }

        $record = $this->medicalRecordService->updateRecord($record, $request->validated());

        return $this->success('Medical record updated successfully', [
            'record' => $record,
        ]);
    }

    /**
     * Delete a medical record (soft delete).
     * DELETE /api/v1/pets/{petId}/medical-records/{uuid}
     */
    public function destroy(Request $request, int $petId, string $uuid): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $record = $this->medicalRecordService->findRecordForPet($pet, $uuid);

        if (!$record) {
            return $this->notFound('Medical record not found');
        }

        $this->medicalRecordService->deleteRecord($record);

        return $this->success('Medical record deleted successfully');
    }
}
