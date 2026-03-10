<?php

namespace App\Services;

use App\Models\VisitRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisitService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Create a visit record for an appointment or SOS.
     */
    public function create(array $data): VisitRecord
    {
        return DB::transaction(function () use ($data) {
            $record = VisitRecord::create([
                'appointment_id' => $data['appointment_id'] ?? null,
                'sos_request_id' => $data['sos_request_id'] ?? null,
                'vet_profile_id' => $data['vet_profile_id'],
                'user_id' => $data['user_id'],
                'pet_id' => $data['pet_id'] ?? null,
                'visit_notes' => $data['visit_notes'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'prescription_file_url' => $data['prescription_file_url'] ?? null,
                'before_images' => $data['before_images'] ?? null,
                'after_images' => $data['after_images'] ?? null,
                'treatment_cost_breakdown' => $data['treatment_cost_breakdown'] ?? null,
                'total_treatment_cost' => $data['total_treatment_cost'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'follow_up_notes' => $data['follow_up_notes'] ?? null,
            ]);

            $this->auditService->log(
                $data['vet_profile_id'],
                VisitRecord::class,
                $record->id,
                'created',
                null,
                ['appointment_id' => $data['appointment_id'] ?? null, 'sos_request_id' => $data['sos_request_id'] ?? null],
                'Visit record created'
            );

            return $record->load(['appointment', 'sosRequest', 'pet:id,name,species', 'vetProfile:id,uuid,clinic_name,vet_name']);
        });
    }

    /**
     * Update a visit record.
     */
    public function update(VisitRecord $record, array $data): VisitRecord
    {
        $oldValues = $record->only(['visit_notes', 'diagnosis', 'total_treatment_cost', 'follow_up_date']);

        $record->update(array_filter($data, fn ($v) => $v !== null));

        $this->auditService->log(
            null,
            VisitRecord::class,
            $record->id,
            'updated',
            $oldValues,
            array_intersect_key($data, $oldValues),
            'Visit record updated'
        );

        return $record->fresh(['appointment', 'sosRequest', 'pet:id,name,species', 'vetProfile:id,uuid,clinic_name,vet_name']);
    }

    /**
     * Upload prescription file.
     */
    public function uploadPrescription(VisitRecord $record, $file): VisitRecord
    {
        $path = $file->store('prescriptions', 'public');
        $record->update(['prescription_file_url' => $path]);
        return $record;
    }

    /**
     * Upload before/after images.
     */
    public function uploadImages(VisitRecord $record, array $files, string $type = 'before'): VisitRecord
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store("visit_images/{$type}", 'public');
        }

        $column = $type === 'before' ? 'before_images' : 'after_images';
        $existing = $record->{$column} ?? [];
        $record->update([$column => array_merge($existing, $paths)]);

        return $record;
    }

    /**
     * Get visit record for an appointment.
     */
    public function getForAppointment(int $appointmentId): ?VisitRecord
    {
        return VisitRecord::where('appointment_id', $appointmentId)
            ->with(['vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species'])
            ->first();
    }

    /**
     * Get visit record for SOS.
     */
    public function getForSos(int $sosRequestId): ?VisitRecord
    {
        return VisitRecord::where('sos_request_id', $sosRequestId)
            ->with(['vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species'])
            ->first();
    }
}
