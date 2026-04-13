<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\PetMedicalRecord;
use App\Models\User;
use App\Models\VisitRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PetMedicalRecordService
{
    /**
     * Return a paginated list of medical records for a pet, with optional filters.
     */
    public function getRecordsForPet(
        Pet $pet,
        ?string $type = null,
        ?string $from = null,
        ?string $to = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $pet->medicalRecords()
            ->with(['recordedByUser:id,name', 'recordedByVet:id,uuid,vet_name,clinic_name'])
            ->withTrashed(false);

        if ($type) {
            $query->byType($type);
        }

        $query->inDateRange($from, $to);

        return $query->paginate($perPage);
    }

    /**
     * Find a single medical record scoped to a pet.
     */
    public function findRecordForPet(Pet $pet, string $uuid): ?PetMedicalRecord
    {
        return $pet->medicalRecords()
            ->with(['recordedByUser:id,name', 'recordedByVet:id,uuid,vet_name,clinic_name', 'visitRecord:id,uuid'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Create a new medical record for a pet.
     * The $actor is either a User (pet owner) or a VetProfile (via VisitRecord linkage).
     */
    public function createRecord(Pet $pet, User $actor, array $data): PetMedicalRecord
    {
        $payload = array_merge($data, [
            'pet_id' => $pet->id,
            'recorded_by_user_id' => $actor->id,
        ]);

        // If the actor is a vet, also store the vet profile reference
        if ($actor->isVet() && $actor->vetProfile) {
            $payload['recorded_by_vet_id'] = $actor->vetProfile->id;
        }

        return PetMedicalRecord::create($payload);
    }

    /**
     * Create a medical record linked to a VisitRecord (called after vet completes a visit).
     */
    public function createFromVisitRecord(VisitRecord $visitRecord, array $data): PetMedicalRecord
    {
        $payload = array_merge($data, [
            'pet_id' => $visitRecord->pet_id,
            'recorded_by_vet_id' => $visitRecord->vet_profile_id,
            'recorded_by_user_id' => $visitRecord->user_id,
            'visit_record_id' => $visitRecord->id,
        ]);

        return PetMedicalRecord::create($payload);
    }

    /**
     * Update a medical record.
     */
    public function updateRecord(PetMedicalRecord $record, array $data): PetMedicalRecord
    {
        $record->update($data);
        return $record->fresh(['recordedByUser:id,name', 'recordedByVet:id,uuid,vet_name,clinic_name']);
    }

    /**
     * Soft-delete a medical record.
     */
    public function deleteRecord(PetMedicalRecord $record): bool
    {
        return (bool) $record->delete();
    }
}
