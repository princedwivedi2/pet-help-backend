<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\PetNote;
use App\Models\PetDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PetManagementService
{
    /**
     * Get comprehensive pet dashboard data.
     */
    public function getPetDashboard(Pet $pet, User $user): array
    {
        // Load all relationships with counts
        $pet->loadCount([
            'notes',
            'reminders',
            'documents', 
            'medications',
            'activeMedications',
            'upcomingReminders',
            'overdueReminders',
        ]);

        // Recent notes (last 5)
        $recentNotes = $pet->notes()
            ->with(['user:id,name'])
            ->limit(5)
            ->get();

        // Upcoming reminders (next 7 days)
        $upcomingReminders = $pet->reminders()
            ->upcoming(7)
            ->with(['user:id,name', 'medication:id,medication_name'])
            ->limit(10)
            ->get();

        // Overdue reminders
        $overdueReminders = $pet->reminders()
            ->overdue()
            ->with(['user:id,name', 'medication:id,medication_name'])
            ->limit(5)
            ->get();

        // Active medications
        $activeMedications = $pet->activeMedications()
            ->with(['prescribedBy:id,vet_name,clinic_name'])
            ->get();

        // Medications needing refill
        $medicationsNeedingRefill = $pet->medications()
            ->needingRefill()
            ->with(['prescribedBy:id,vet_name,clinic_name'])
            ->get();

        // Recent documents (last 5)
        $recentDocuments = $pet->documents()
            ->with(['vetProfile:id,vet_name,clinic_name'])
            ->limit(5)
            ->get();

        // Documents expiring soon
        $expiringDocuments = $pet->documents()
            ->expiringSoon(30)
            ->with(['vetProfile:id,vet_name,clinic_name'])
            ->get();

        // Health summary
        $healthSummary = $this->generateHealthSummary($pet);

        return [
            'pet' => $pet,
            'summary' => [
                'total_notes' => $pet->notes_count,
                'total_reminders' => $pet->reminders_count,
                'upcoming_reminders' => $pet->upcoming_reminders_count,
                'overdue_reminders' => $pet->overdue_reminders_count,
                'total_documents' => $pet->documents_count,
                'total_medications' => $pet->medications_count,
                'active_medications' => $pet->active_medications_count,
                'age_in_months' => $pet->getAgeInMonths(),
                'age_string' => $pet->getAgeString(),
            ],
            'recent_notes' => $recentNotes,
            'upcoming_reminders' => $upcomingReminders,
            'overdue_reminders' => $overdueReminders,
            'active_medications' => $activeMedications,
            'medications_needing_refill' => $medicationsNeedingRefill,
            'recent_documents' => $recentDocuments,
            'expiring_documents' => $expiringDocuments,
            'health_summary' => $healthSummary,
        ];
    }

    /**
     * Create a new pet note with optional photo uploads.
     */
    public function createNote(Pet $pet, User $user, array $data, array $photos = []): PetNote
    {
        // Upload photos if provided
        $photoUrls = [];
        foreach ($photos as $photo) {
            $photoUrls[] = $this->uploadPhoto($photo, "pets/{$pet->id}/notes");
        }

        $data['pet_id'] = $pet->id;
        $data['user_id'] = $user->id;
        $data['photo_urls'] = $photoUrls;

        return PetNote::create($data);
    }

    /**
     * Upload a document for a pet.
     */
    public function uploadDocument(Pet $pet, User $user, array $data, UploadedFile $file): PetDocument
    {
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $filePath = "pets/{$pet->id}/documents/{$filename}";

        // Store file
        $file->storeAs(dirname($filePath), basename($filePath), 'private');

        // Create document record
        $data['pet_id'] = $pet->id;
        $data['user_id'] = $user->id;
        $data['file_path'] = $filePath;
        $data['file_name'] = $file->getClientOriginalName();
        $data['file_size'] = $file->getSize();
        $data['mime_type'] = $file->getMimeType();

        // Generate QR code data for easy sharing
        $document = PetDocument::create($data);
        $document->update([
            'qr_code_data' => $document->generateQrCode(),
        ]);

        return $document;
    }

    /**
     * Upload a photo and return the URL.
     */
    private function uploadPhoto(UploadedFile $photo, string $path): string
    {
        $extension = $photo->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $filePath = "{$path}/{$filename}";

        $photo->storeAs(dirname($filePath), basename($filePath), 'public');

        return Storage::url($filePath);
    }

    /**
     * Generate a health summary for the pet.
     */
    private function generateHealthSummary(Pet $pet): array
    {
        // Recent health notes
        $healthNotes = $pet->notes()
            ->byType('health')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        // Average mood and activity from recent notes
        $recentNotesWithRatings = $pet->notes()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('mood_rating')
            ->orWhereNotNull('activity_level')
            ->get();

        $avgMood = $recentNotesWithRatings->avg('mood_rating');
        $avgActivity = $recentNotesWithRatings->avg('activity_level');

        // Vaccination status (check for recent vaccination records)
        $recentVaccinations = $pet->documents()
            ->byType('vaccination_record')
            ->where('document_date', '>=', now()->subYear())
            ->count();

        // Weight tracking (from recent health notes)
        $weightEntries = $pet->notes()
            ->byType('health')
            ->where('created_at', '>=', now()->subMonths(6))
            ->whereNotNull('content')
            ->get()
            ->filter(function ($note) {
                return str_contains(strtolower($note->content), 'weight');
            })
            ->take(5);

        return [
            'health_notes_last_30_days' => $healthNotes->count(),
            'avg_mood_last_7_days' => $avgMood ? round($avgMood, 1) : null,
            'avg_activity_last_7_days' => $avgActivity ? round($avgActivity, 1) : null,
            'vaccination_status' => $recentVaccinations > 0 ? 'up_to_date' : 'needs_attention',
            'recent_vaccinations_count' => $recentVaccinations,
            'weight_tracking_entries' => $weightEntries->count(),
            'last_health_note_date' => $healthNotes->first()?->created_at,
        ];
    }

    /**
     * Create medication reminders when a vet prescribes medication.
     */
    public function createMedicationFromPrescription(Pet $pet, User $user, array $prescriptionData): PetMedication
    {
        $prescriptionData['pet_id'] = $pet->id;
        $prescriptionData['user_id'] = $user->id;
        $prescriptionData['is_active'] = true;
        $prescriptionData['reminder_enabled'] = true;

        $medication = PetMedication::create($prescriptionData);

        // Auto-create dosage reminders
        $medication->createDosageReminders();

        return $medication;
    }

    /**
     * Get medication adherence statistics.
     */
    public function getMedicationAdherence(Pet $pet, int $days = 30): array
    {
        $activeMedications = $pet->activeMedications()
            ->with(['dosageLogs' => function ($query) use ($days) {
                $query->where('administered_at', '>=', now()->subDays($days));
            }])
            ->get();

        $adherenceData = [];

        foreach ($activeMedications as $medication) {
            $expectedDoses = $this->calculateExpectedDoses($medication, $days);
            $actualDoses = $medication->dosageLogs->count();
            $successfulDoses = $medication->dosageLogs->where('was_successful', true)->count();

            $adherenceData[] = [
                'medication_id' => $medication->id,
                'medication_name' => $medication->medication_name,
                'expected_doses' => $expectedDoses,
                'actual_doses' => $actualDoses,
                'successful_doses' => $successfulDoses,
                'adherence_rate' => $expectedDoses > 0 ? round(($actualDoses / $expectedDoses) * 100, 1) : 0,
                'success_rate' => $actualDoses > 0 ? round(($successfulDoses / $actualDoses) * 100, 1) : 0,
            ];
        }

        return $adherenceData;
    }

    /**
     * Calculate expected number of doses for a medication over a period.
     */
    private function calculateExpectedDoses(PetMedication $medication, int $days): int
    {
        if (!$medication->is_active || $medication->frequency_unit === 'as_needed') {
            return 0;
        }

        $startDate = max($medication->start_date ?? now(), now()->subDays($days));
        $endDate = min($medication->end_date ?? now(), now());
        $periodDays = $startDate->diffInDays($endDate);

        return match ($medication->frequency_unit) {
            'daily' => $periodDays * $medication->frequency,
            'weekly' => (int) floor($periodDays / 7) * $medication->frequency,
            'monthly' => (int) floor($periodDays / 30) * $medication->frequency,
            'hours' => (int) floor(($periodDays * 24) / $medication->frequency),
            default => 0,
        };
    }

    /**
     * Export pet data for sharing with vets.
     */
    public function exportPetData(Pet $pet, array $options = []): array
    {
        $data = [
            'pet_info' => [
                'name' => $pet->name,
                'species' => $pet->species,
                'breed' => $pet->breed,
                'birth_date' => $pet->birth_date?->format('Y-m-d'),
                'age' => $pet->getAgeString(),
                'weight_kg' => $pet->weight_kg,
                'medical_notes' => $pet->medical_notes,
            ],
        ];

        if ($options['include_medications'] ?? true) {
            $data['medications'] = $pet->medications()
                ->with(['prescribedBy:id,vet_name,clinic_name'])
                ->get()
                ->map(function ($med) {
                    return [
                        'name' => $med->medication_name,
                        'dosage' => $med->dosage . ' ' . $med->dosage_unit,
                        'frequency' => $med->frequency . ' ' . $med->frequency_unit,
                        'start_date' => $med->start_date?->format('Y-m-d'),
                        'end_date' => $med->end_date?->format('Y-m-d'),
                        'prescribed_by' => $med->prescribedBy?->vet_name,
                        'is_active' => $med->is_active,
                    ];
                });
        }

        if ($options['include_health_notes'] ?? true) {
            $data['health_notes'] = $pet->notes()
                ->byType('health')
                ->where('is_private', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($note) {
                    return [
                        'date' => $note->created_at->format('Y-m-d H:i'),
                        'content' => $note->content,
                        'mood_rating' => $note->mood_rating,
                        'activity_level' => $note->activity_level,
                    ];
                });
        }

        if ($options['include_documents'] ?? false) {
            $data['document_summary'] = $pet->documents()
                ->where('is_confidential', false)
                ->get()
                ->groupBy('document_type')
                ->map(function ($docs, $type) {
                    return [
                        'count' => $docs->count(),
                        'latest_date' => $docs->max('document_date'),
                    ];
                });
        }

        $data['generated_at'] = now()->toIso8601String();
        $data['generated_for'] = 'Veterinary consultation';

        return $data;
    }
}