<?php

namespace App\Services;

use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VetVerificationService
{
    /**
     * Mandatory profile fields that must be non-null and non-empty before approval.
     */
    private const REQUIRED_PROFILE_FIELDS = [
        'vet_name'            => 'full_name',
        'phone'               => 'phone',
        'clinic_name'         => 'clinic_name',
        'address'             => 'clinic_address',
        'qualifications'      => 'qualification',
        'years_of_experience' => 'experience',
        'license_number'      => 'registration_number',
    ];

    /**
     * Required document types and their corresponding profile fields/paths.
     */
    private const REQUIRED_DOCUMENTS = [
        'license_proof' => 'license_document_url',
    ];

    /**
     * Optional document types checked during review.
     */
    private const OPTIONAL_DOCUMENTS = [
        'identity_proof'      => null,
        'qualification_proof' => null,
    ];

    // ─── Profile Completeness ───────────────────────────────────────

    /**
     * Check which mandatory fields are missing from the vet profile.
     *
     * @return string[] List of missing field display names
     */
    public function getMissingFields(VetProfile $vetProfile): array
    {
        $missing = [];

        foreach (self::REQUIRED_PROFILE_FIELDS as $column => $displayName) {
            $value = $vetProfile->{$column};

            if ($value === null || (is_string($value) && trim($value) === '')) {
                $missing[] = $displayName;
            }
        }

        return $missing;
    }

    /**
     * Check if a vet profile is complete (all mandatory fields present).
     */
    public function isProfileComplete(VetProfile $vetProfile): bool
    {
        return count($this->getMissingFields($vetProfile)) === 0;
    }

    // ─── Document Verification ──────────────────────────────────────

    /**
     * Build a map of document presence: { type => true/false }.
     * Also validates that referenced files actually exist in storage.
     */
    public function getDocumentPresenceMap(VetProfile $vetProfile): array
    {
        $map = [];

        // Check required documents
        foreach (self::REQUIRED_DOCUMENTS as $docType => $profileField) {
            $path = $profileField ? $vetProfile->{$profileField} : null;
            $map[$docType] = $path && Storage::disk('public')->exists($path);
        }

        // Check optional documents via verification logs (documents uploaded post-apply)
        $uploadedTypes = $this->getUploadedDocumentTypes($vetProfile);

        foreach (self::OPTIONAL_DOCUMENTS as $docType => $_) {
            $map[$docType] = in_array($docType, $uploadedTypes, true);
        }

        return $map;
    }

    /**
     * Get list of document types that have been uploaded for this vet.
     * Looks at VetVerification entries with 'applied' action for document_paths,
     * and any additional document uploads.
     */
    private function getUploadedDocumentTypes(VetProfile $vetProfile): array
    {
        $types = [];

        // The license doc is always stored on the profile itself
        if ($vetProfile->license_document_url) {
            $types[] = 'license_proof';
        }

        // Check verified_fields from verification records for additional document references
        $records = VetVerification::where('vet_profile_id', $vetProfile->id)
            ->whereNotNull('verified_fields')
            ->get();

        foreach ($records as $record) {
            $fields = $record->verified_fields;
            if (!empty($fields['document_type'])) {
                $types[] = $fields['document_type'];
            }
        }

        return array_unique($types);
    }

    /**
     * Validate that all required documents exist and are accessible.
     *
     * @return string[] List of missing or corrupted document types
     */
    public function getMissingDocuments(VetProfile $vetProfile): array
    {
        $missing = [];

        foreach (self::REQUIRED_DOCUMENTS as $docType => $profileField) {
            $path = $profileField ? $vetProfile->{$profileField} : null;

            if (!$path) {
                $missing[] = $docType;
                continue;
            }

            if (!Storage::disk('public')->exists($path)) {
                $missing[] = $docType;

                Log::warning('Vet document file missing from storage', [
                    'vet_profile_id' => $vetProfile->id,
                    'document_type'  => $docType,
                    'expected_path'  => $path,
                ]);
            }
        }

        return $missing;
    }

    // ─── Approval Eligibility ───────────────────────────────────────

    /**
     * Determine whether a vet profile is eligible for admin approval.
     * Returns a structured result with reasons if not eligible.
     */
    public function checkApprovalEligibility(VetProfile $vetProfile): array
    {
        $eligible = true;
        $reasons  = [];

        // 1. Status check
        if ($vetProfile->isApproved()) {
            $eligible = false;
            $reasons[] = 'Vet is already approved.';
        }

        if ($vetProfile->isRejected()) {
            $eligible = false;
            $reasons[] = 'Vet was previously rejected. A new application is required.';
        }

        if ($vetProfile->isSuspended()) {
            $eligible = false;
            $reasons[] = 'Vet is suspended. Use reactivation instead of approval.';
        }

        if (!$vetProfile->isPending()) {
            $eligible = false;
            $reasons[] = "Current status '{$vetProfile->vet_status}' does not allow approval.";
        }

        // 2. Profile completeness
        $missingFields = $this->getMissingFields($vetProfile);
        if (count($missingFields) > 0) {
            $eligible = false;
            $reasons[] = 'Profile is incomplete. Missing fields: ' . implode(', ', $missingFields);
        }

        // 3. Document verification
        $missingDocs = $this->getMissingDocuments($vetProfile);
        if (count($missingDocs) > 0) {
            $eligible = false;
            $reasons[] = 'Required documents missing: ' . implode(', ', $missingDocs);
        }

        return [
            'eligible'        => $eligible,
            'reasons'         => $reasons,
            'missing_fields'  => $missingFields ?? [],
            'missing_documents' => $missingDocs ?? [],
        ];
    }

    // ─── Profile Snapshot ───────────────────────────────────────────

    /**
     * Capture a JSON snapshot of the vet profile at the moment of verification.
     * This preserves the data as it was when the admin made the decision.
     */
    public function captureProfileSnapshot(VetProfile $vetProfile): array
    {
        return [
            'vet_name'            => $vetProfile->vet_name,
            'email'               => $vetProfile->email,
            'phone'               => $vetProfile->phone,
            'clinic_name'         => $vetProfile->clinic_name,
            'address'             => $vetProfile->address,
            'state'               => $vetProfile->state,
            'qualifications'      => $vetProfile->qualifications,
            'license_number'      => $vetProfile->license_number,
            'years_of_experience' => $vetProfile->years_of_experience,
            'accepted_species'    => $vetProfile->accepted_species,
            'services'            => $vetProfile->services,
            'snapshot_at'         => now()->toIso8601String(),
        ];
    }

    /**
     * Capture a snapshot of document paths and their storage status.
     */
    public function captureDocumentSnapshot(VetProfile $vetProfile): array
    {
        $snapshot = [];

        if ($vetProfile->license_document_url) {
            $snapshot['license_proof'] = [
                'path'   => $vetProfile->license_document_url,
                'exists' => Storage::disk('public')->exists($vetProfile->license_document_url),
            ];
        }

        return $snapshot;
    }

    // ─── Verification Record Creation ───────────────────────────────

    /**
     * Create a persistent VetVerification record for any admin action.
     * This is the primary audit artifact for all vet verification actions.
     */
    public function createVerificationRecord(
        VetProfile $vetProfile,
        User $admin,
        string $action,
        ?string $notes = null
    ): VetVerification {
        return VetVerification::create([
            'vet_profile_id'  => $vetProfile->id,
            'admin_id'        => $admin->id,
            'action'          => $action,
            'notes'           => $notes,
            'verified_fields' => $this->captureProfileSnapshot($vetProfile),
            'document_snapshot' => $this->captureDocumentSnapshot($vetProfile),
            'missing_fields'  => $this->getMissingFields($vetProfile),
        ]);
    }

    // ─── Review Data (for Admin Review Endpoint) ────────────────────

    /**
     * Build the full review payload for an admin reviewing a vet application.
     */
    public function buildReviewData(VetProfile $vetProfile): array
    {
        $missingFields = $this->getMissingFields($vetProfile);
        $missingDocs   = $this->getMissingDocuments($vetProfile);
        $documentMap   = $this->getDocumentPresenceMap($vetProfile);
        $eligibility   = $this->checkApprovalEligibility($vetProfile);

        return [
            'vet_profile'           => $vetProfile->load([
                'user:id,name,email,phone,created_at',
            ]),
            'eligible_for_approval' => $eligibility['eligible'],
            'eligibility_reasons'   => $eligibility['reasons'],
            'missing_fields'        => $missingFields,
            'missing_documents'     => $missingDocs,
            'documents'             => $documentMap,
            'profile_complete'      => count($missingFields) === 0,
            'documents_verified'    => count($missingDocs) === 0,
            'verification_history'  => VetVerification::where('vet_profile_id', $vetProfile->id)
                ->with('admin:id,name')
                ->orderByDesc('created_at')
                ->get(),
        ];
    }
}
