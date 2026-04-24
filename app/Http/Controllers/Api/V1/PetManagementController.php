<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\PetNote;
use App\Models\PetReminder;
use App\Models\PetDocument;
use App\Models\PetMedication;
use App\Models\PetMedicationLog;
use App\Services\PetManagementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PetManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PetManagementService $petManagementService
    ) {}

    // ──── Pet Overview ────────────────────────────────────────────

    /**
     * Get comprehensive pet dashboard with all related data.
     * GET /api/v1/pets/{pet}/dashboard
     */
    public function dashboard(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $dashboard = $this->petManagementService->getPetDashboard($pet, $request->user());

        return $this->success('Pet dashboard retrieved', $dashboard);
    }

    // ──── Pet Notes ────────────────────────────────────────────

    /**
     * List pet notes with filters.
     * GET /api/v1/pets/{pet}/notes
     */
    public function noteIndex(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $notes = $pet->notes()
            ->with(['user:id,name'])
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->when($request->favorites, fn($q) => $q->favorites())
            ->when($request->search, function($q, $search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                       ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->paginate($request->per_page ?? 15);

        return $this->success('Pet notes retrieved', [
            'notes' => $notes->items(),
            'pagination' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
        ]);
    }

    /**
     * Store a new pet note.
     * POST /api/v1/pets/{pet}/notes
     */
    public function noteStore(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'note_type' => ['required', Rule::in(['daily', 'health', 'behavior', 'feeding', 'exercise', 'training', 'grooming', 'vet_visit', 'medication', 'other'])],
            'mood_rating' => ['nullable', 'integer', 'between:1,10'],
            'activity_level' => ['nullable', 'integer', 'between:1,10'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'reminder_at' => ['nullable', 'date', 'after:now'],
            'is_favorite' => ['boolean'],
            'is_private' => ['boolean'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'], // 5MB max per photo
        ]);

        $note = $this->petManagementService->createNote($pet, $request->user(), $validated, $request->file('photos', []));

        return $this->success('Note created successfully', ['note' => $note], 201);
    }

    /**
     * Show a specific note.
     * GET /api/v1/pets/{pet}/notes/{note}
     */
    public function noteShow(Pet $pet, PetNote $note): JsonResponse
    {
        $this->authorize('view', $pet);
        
        if ($note->pet_id !== $pet->id) {
            return $this->notFound('Note not found for this pet');
        }

        $note->load(['user:id,name']);

        return $this->success('Note retrieved', ['note' => $note]);
    }

    /**
     * Update a pet note.
     * PUT /api/v1/pets/{pet}/notes/{note}
     */
    public function noteUpdate(Request $request, Pet $pet, PetNote $note): JsonResponse
    {
        $this->authorize('update', $pet);
        
        if ($note->pet_id !== $pet->id) {
            return $this->notFound('Note not found for this pet');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'note_type' => ['required', Rule::in(['daily', 'health', 'behavior', 'feeding', 'exercise', 'training', 'grooming', 'vet_visit', 'medication', 'other'])],
            'mood_rating' => ['nullable', 'integer', 'between:1,10'],
            'activity_level' => ['nullable', 'integer', 'between:1,10'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'reminder_at' => ['nullable', 'date', 'after:now'],
            'is_favorite' => ['boolean'],
            'is_private' => ['boolean'],
        ]);

        $note->update($validated);

        return $this->success('Note updated successfully', ['note' => $note]);
    }

    /**
     * Delete a pet note.
     * DELETE /api/v1/pets/{pet}/notes/{note}
     */
    public function noteDestroy(Pet $pet, PetNote $note): JsonResponse
    {
        $this->authorize('update', $pet);
        
        if ($note->pet_id !== $pet->id) {
            return $this->notFound('Note not found for this pet');
        }

        $note->delete();

        return $this->success('Note deleted successfully');
    }

    // ──── Pet Reminders ────────────────────────────────────────

    /**
     * List pet reminders.
     * GET /api/v1/pets/{pet}/reminders
     */
    public function reminderIndex(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $query = $pet->reminders()
            ->with(['user:id,name', 'medication:id,medication_name']);

        // Apply filters
        if ($request->status) {
            match($request->status) {
                'pending' => $query->pending(),
                'overdue' => $query->overdue(),
                'completed' => $query->completed(),
                default => null,
            };
        }

        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->priority) {
            $query->where('priority', '>=', $request->priority);
        }

        $reminders = $query->paginate($request->per_page ?? 15);

        return $this->success('Pet reminders retrieved', [
            'reminders' => $reminders->items(),
            'pagination' => [
                'current_page' => $reminders->currentPage(),
                'last_page' => $reminders->lastPage(),
                'per_page' => $reminders->perPage(),
                'total' => $reminders->total(),
            ],
        ]);
    }

    /**
     * Store a new reminder.
     * POST /api/v1/pets/{pet}/reminders
     */
    public function reminderStore(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reminder_type' => ['required', Rule::in(['vaccination', 'medication', 'checkup', 'grooming', 'feeding', 'exercise', 'training', 'deworming', 'flea_treatment', 'dental_care', 'weight_check', 'other'])],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'frequency' => ['nullable', 'integer', 'min:1'],
            'frequency_unit' => ['nullable', Rule::in(['minutes', 'hours', 'days', 'weeks', 'months', 'years'])],
            'end_date' => ['nullable', 'date', 'after:scheduled_at'],
            'notification_methods' => ['nullable', 'array'],
            'notification_methods.*' => [Rule::in(['database', 'email', 'sms', 'push'])],
            'advance_notice_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'], // Max 1 week
            'priority' => ['nullable', 'integer', 'between:1,10'],
            'location' => ['nullable', 'string', 'max:255'],
            'cost_estimate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['pet_id'] = $pet->id;
        $validated['user_id'] = $request->user()->id;

        $reminder = PetReminder::create($validated);

        return $this->success('Reminder created successfully', ['reminder' => $reminder], 201);
    }

    /**
     * Update a pet reminder.
     * PUT /api/v1/pets/{pet}/reminders/{reminder}
     */
    public function reminderUpdate(Request $request, Pet $pet, PetReminder $reminder): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($reminder->pet_id !== $pet->id) {
            return $this->notFound('Reminder not found for this pet');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reminder_type' => ['required', Rule::in(['vaccination', 'medication', 'checkup', 'grooming', 'feeding', 'exercise', 'training', 'deworming', 'flea_treatment', 'dental_care', 'weight_check', 'other'])],
            'scheduled_at' => ['required', 'date'],
            'frequency' => ['nullable', 'integer', 'min:1'],
            'frequency_unit' => ['nullable', Rule::in(['minutes', 'hours', 'days', 'weeks', 'months', 'years'])],
            'end_date' => ['nullable', 'date', 'after:scheduled_at'],
            'notification_methods' => ['nullable', 'array'],
            'notification_methods.*' => [Rule::in(['database', 'email', 'sms', 'push'])],
            'advance_notice_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'priority' => ['nullable', 'integer', 'between:1,10'],
            'location' => ['nullable', 'string', 'max:255'],
            'cost_estimate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reminder->update($validated);

        return $this->success('Reminder updated successfully', ['reminder' => $reminder]);
    }

    /**
     * Delete a pet reminder.
     * DELETE /api/v1/pets/{pet}/reminders/{reminder}
     */
    public function reminderDestroy(Pet $pet, PetReminder $reminder): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($reminder->pet_id !== $pet->id) {
            return $this->notFound('Reminder not found for this pet');
        }

        $reminder->delete();

        return $this->success('Reminder deleted successfully');
    }

    /**
     * Mark reminder as completed.
     * POST /api/v1/pets/{pet}/reminders/{reminder}/complete
     */
    public function reminderComplete(Pet $pet, PetReminder $reminder): JsonResponse
    {
        $this->authorize('update', $pet);
        
        if ($reminder->pet_id !== $pet->id) {
            return $this->notFound('Reminder not found for this pet');
        }

        $reminder->markCompleted();

        // Create next occurrence if recurring
        if ($nextReminder = $reminder->getNextOccurrence()) {
            $nextReminder->save();
        }

        return $this->success('Reminder marked as completed', ['reminder' => $reminder]);
    }

    // ──── Pet Documents ────────────────────────────────────────

    /**
     * List pet documents.
     * GET /api/v1/pets/{pet}/documents
     */
    public function documentIndex(Request $request, Pet $pet): JsonResponse
    {
        $accessError = $this->ensureDocumentReadAccess($request->user(), $pet);
        if ($accessError instanceof JsonResponse) {
            return $accessError;
        }

        $documents = $pet->documents()
            ->with(['user:id,name', 'vetProfile:id,vet_name,clinic_name'])
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->when($request->vet_id, fn($q, $vetId) => $q->where('vet_profile_id', $vetId))
            ->when($request->search, function($q, $search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->paginate($request->per_page ?? 15);

        return $this->success('Pet documents retrieved', [
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Store a new document.
     * POST /api/v1/pets/{pet}/documents
     */
    public function documentStore(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document_type' => ['required', Rule::in(['vaccination_record', 'medical_report', 'prescription', 'lab_result', 'x_ray', 'insurance_policy', 'registration', 'microchip_info', 'pedigree', 'health_certificate', 'grooming_record', 'photo', 'video', 'other'])],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:document_date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_confidential' => ['boolean'],
            'file' => ['required', 'file', 'max:20480'], // 20MB max
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'vet_profile_id' => ['nullable', 'exists:vet_profiles,id'],
        ]);

        $document = $this->petManagementService->uploadDocument($pet, $request->user(), $validated, $request->file('file'));

        return $this->success('Document uploaded successfully', ['document' => $document], 201);
    }

    /**
     * Update document metadata (not the file itself).
     * PUT /api/v1/pets/{pet}/documents/{document}
     */
    public function documentUpdate(Request $request, Pet $pet, PetDocument $document): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($document->pet_id !== $pet->id) {
            return $this->notFound('Document not found for this pet');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document_type' => ['required', Rule::in(['vaccination_record', 'medical_report', 'prescription', 'lab_result', 'x_ray', 'insurance_policy', 'registration', 'microchip_info', 'pedigree', 'health_certificate', 'grooming_record', 'photo', 'video', 'other'])],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:document_date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_confidential' => ['boolean'],
        ]);

        $document->update($validated);

        return $this->success('Document updated successfully', ['document' => $document]);
    }

    /**
     * Delete a document and its stored file.
     * DELETE /api/v1/pets/{pet}/documents/{document}
     */
    public function documentDestroy(Pet $pet, PetDocument $document): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($document->pet_id !== $pet->id) {
            return $this->notFound('Document not found for this pet');
        }

        // Remove the physical file before soft-deleting the record
        if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
        }

        $document->delete();

        return $this->success('Document deleted successfully');
    }

    /**
     * Download a document.
     * GET /api/v1/pets/{pet}/documents/{document}/download
     */
    public function documentDownload(Pet $pet, PetDocument $document): JsonResponse
    {
        $accessError = $this->ensureDocumentReadAccess(request()->user(), $pet);
        if ($accessError instanceof JsonResponse) {
            return $accessError;
        }
        
        if ($document->pet_id !== $pet->id) {
            return $this->notFound('Document not found for this pet');
        }

        if (!Storage::disk('private')->exists($document->file_path)) {
            return $this->notFound('Document file not found');
        }

        $url = Storage::disk('private')->temporaryUrl($document->file_path, now()->addMinutes(60));

        return $this->success('Document download URL generated', [
            'download_url' => $url,
            'expires_at' => now()->addMinutes(60),
        ]);
    }

    private function ensureDocumentReadAccess($user, Pet $pet): ?JsonResponse
    {
        if (!$user) {
            return $this->unauthorized('Unauthenticated.');
        }

        if ($user->id === $pet->user_id || $user->isAdmin()) {
            return null;
        }

        return $this->forbidden('Only the pet owner or admin can access pet documents.');
    }

    // ──── Pet Medications ──────────────────────────────────────

    /**
     * List pet medications.
     * GET /api/v1/pets/{pet}/medications
     */
    public function medicationIndex(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $query = $pet->medications()
            ->with(['prescribedBy:id,vet_name,clinic_name', 'user:id,name']);

        if ($request->status === 'active') {
            $query->active();
        } elseif ($request->status === 'expired') {
            $query->expired();
        }

        $medications = $query->paginate($request->per_page ?? 15);

        return $this->success('Pet medications retrieved', [
            'medications' => $medications->items(),
            'pagination' => [
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'per_page' => $medications->perPage(),
                'total' => $medications->total(),
            ],
        ]);
    }

    /**
     * Store a new medication (usually from vet prescription).
     * POST /api/v1/pets/{pet}/medications
     */
    public function medicationStore(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $validated = $request->validate([
            'medication_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:100'],
            'dosage_unit' => ['required', 'string', 'max:50'],
            'frequency' => ['required', 'integer', 'min:1'],
            'frequency_unit' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'as_needed', 'hours'])],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'administration_method' => ['required', Rule::in(['oral', 'topical', 'injection', 'drops', 'spray', 'inhaler', 'patch', 'suppository', 'other'])],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'side_effects' => ['nullable', 'string', 'max:1000'],
            'food_instructions' => ['nullable', 'string', 'max:500'],
            'storage_instructions' => ['nullable', 'string', 'max:500'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'pharmacy_name' => ['nullable', 'string', 'max:255'],
            'prescription_number' => ['nullable', 'string', 'max:100'],
            'total_refills' => ['nullable', 'integer', 'min:0'],
            'reminder_enabled' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'prescribed_by_vet_id' => ['nullable', 'exists:vet_profiles,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        $validated['pet_id'] = $pet->id;
        $validated['user_id'] = $request->user()->id;
        $validated['refills_remaining'] = $validated['total_refills'] ?? 0;

        $medication = PetMedication::create($validated);

        return $this->success('Medication added successfully', ['medication' => $medication], 201);
    }

    /**
     * Update an existing medication.
     * PUT /api/v1/pets/{pet}/medications/{medication}
     */
    public function medicationUpdate(Request $request, Pet $pet, PetMedication $medication): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($medication->pet_id !== $pet->id) {
            return $this->notFound('Medication not found for this pet');
        }

        $validated = $request->validate([
            'medication_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:100'],
            'dosage_unit' => ['required', 'string', 'max:50'],
            'frequency' => ['required', 'integer', 'min:1'],
            'frequency_unit' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'as_needed', 'hours'])],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'administration_method' => ['required', Rule::in(['oral', 'topical', 'injection', 'drops', 'spray', 'inhaler', 'patch', 'suppository', 'other'])],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'side_effects' => ['nullable', 'string', 'max:1000'],
            'food_instructions' => ['nullable', 'string', 'max:500'],
            'storage_instructions' => ['nullable', 'string', 'max:500'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'pharmacy_name' => ['nullable', 'string', 'max:255'],
            'prescription_number' => ['nullable', 'string', 'max:100'],
            'total_refills' => ['nullable', 'integer', 'min:0'],
            'reminder_enabled' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'prescribed_by_vet_id' => ['nullable', 'exists:vet_profiles,id'],
        ]);

        $medication->update($validated);

        return $this->success('Medication updated successfully', ['medication' => $medication]);
    }

    /**
     * Delete a pet medication.
     * DELETE /api/v1/pets/{pet}/medications/{medication}
     */
    public function medicationDestroy(Pet $pet, PetMedication $medication): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($medication->pet_id !== $pet->id) {
            return $this->notFound('Medication not found for this pet');
        }

        $medication->delete();

        return $this->success('Medication deleted successfully');
    }

    /**
     * Discontinue a medication (mark inactive with reason).
     * POST /api/v1/pets/{pet}/medications/{medication}/discontinue
     */
    public function medicationDiscontinue(Request $request, Pet $pet, PetMedication $medication): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($medication->pet_id !== $pet->id) {
            return $this->notFound('Medication not found for this pet');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $medication->discontinue($validated['reason']);

        return $this->success('Medication discontinued', ['medication' => $medication]);
    }

    /**
     * Log medication administration.
     * POST /api/v1/pets/{pet}/medications/{medication}/log
     */
    public function medicationLog(Request $request, Pet $pet, PetMedication $medication): JsonResponse
    {
        $this->authorize('update', $pet);
        
        if ($medication->pet_id !== $pet->id) {
            return $this->notFound('Medication not found for this pet');
        }

        $validated = $request->validate([
            'administered_at' => ['required', 'date', 'before_or_equal:now'],
            'administered_by' => ['nullable', 'string', 'max:255'],
            'dosage_given' => ['required', 'string', 'max:100'],
            'was_successful' => ['required', 'boolean'],
            'pet_reaction' => ['required', Rule::in(['normal', 'positive', 'mild_discomfort', 'refused', 'vomited', 'allergic_reaction', 'other'])],
            'side_effects_observed' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['pet_medication_id'] = $medication->id;
        $validated['user_id'] = $request->user()->id;
        $validated['dosage_unit'] = $medication->dosage_unit;
        $validated['administration_method'] = $medication->administration_method;

        $log = PetMedicationLog::create($validated);

        return $this->success('Medication administration logged', ['log' => $log], 201);
    }
}