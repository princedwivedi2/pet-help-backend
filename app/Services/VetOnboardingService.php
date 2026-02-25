<?php

namespace App\Services;

use App\Exceptions\VetApprovalException;
use App\Exceptions\VetDocumentException;
use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetVerificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VetOnboardingService
{
    public function __construct(
        private VetVerificationService $verificationService
    ) {}
    /**
     * Process a vet application.
     *
     * Creates User (role=vet) + VetProfile (vet_status=pending) in a DB transaction.
     * Password hashing relies on the User model's 'hashed' cast — no Hash::make.
     * Optional documents are stored with UUID filenames.
     *
     * @param  array  $data   Validated form data
     * @param  array  $files  Uploaded document files (UploadedFile[])
     * @return array  ['user', 'vet_profile', 'token', 'documents']
     */
    public function apply(array $data, array $files = []): array
    {
        return DB::transaction(function () use ($data, $files) {
            // Create user — password is auto-hashed by User model cast
            $user = User::create([
                'name'     => $data['full_name'],
                'email'    => $data['email'],
                'password' => $data['password'], // hashed via model cast
                'phone'    => $data['phone_number'] ?? null,
            ]);

            // Set role explicitly — not mass-assignable for security
            $user->role = 'vet';
            $user->save();

            // Store documents with UUID filenames
            $documentPaths = $this->storeDocuments($files);

            // Create vet profile
            $vetProfile = VetProfile::create([
                'user_id'                => $user->id,
                'vet_name'               => $data['full_name'],
                'clinic_name'            => $data['clinic_name'],
                'phone'                  => $data['phone_number'],
                'email'                  => $data['email'],
                'address'                => $data['clinic_address'],
                'city'                   => $data['city'] ?? $this->extractCityFromAddress($data['clinic_address']),
                'state'                  => $data['state'] ?? null,
                'postal_code'            => $data['postal_code'] ?? null,
                'latitude'               => $data['latitude'],
                'longitude'              => $data['longitude'],
                'qualifications'         => $data['qualifications'],
                'license_number'         => $data['license_number'],
                'years_of_experience'    => $data['years_of_experience'],
                'accepted_species'       => $data['accepted_species'],
                'services'               => $data['services_offered'],
                'license_document_url'   => $documentPaths[0] ?? null, // primary doc
                'is_emergency_available' => $data['is_emergency_available'] ?? false,
                'is_24_hours'            => $data['is_24_hours'] ?? false,
                'is_verified'            => false,
                'vet_status'             => 'pending',
                'is_active'              => true,
            ]);

            // Create an initial pending verification log
            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => null,
                'action'         => 'applied',
                'reason'         => 'New vet application submitted',
                'metadata'       => [
                    'license_number'  => $data['license_number'],
                    'vet_name'        => $data['full_name'],
                    'documents_count' => count($documentPaths),
                    'document_paths'  => $documentPaths,
                ],
            ]);

            // Do NOT issue a token — pending vets must wait for admin approval
            // before they can log in (login guard checks vet_status=approved)

            Log::channel('stack')->info('Vet application submitted', [
                'user_id'        => $user->id,
                'vet_profile_id' => $vetProfile->id,
                'license_number' => $data['license_number'],
                'documents'      => count($documentPaths),
            ]);

            return [
                'user'        => $user,
                'vet_profile' => $vetProfile,
                'documents'   => $documentPaths,
            ];
        });
    }

    /**
     * Legacy registration method — delegates to apply().
     */
    public function registerVet(array $data): array
    {
        return $this->apply($data);
    }

    /**
     * Store uploaded documents with UUID filenames.
     *
     * @param  UploadedFile[]  $files
     * @return string[]  Array of stored file paths
     */
    public function storeDocuments(array $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $extension = $file->getClientOriginalExtension();
                $filename  = Str::uuid()->toString() . '.' . $extension;
                $path      = $file->storeAs('vet-documents', $filename, 'public');
                $paths[]   = $path;
            }
        }

        return $paths;
    }

    /**
     * Upload a single document for an existing vet profile.
     */
    public function uploadDocument(VetProfile $vetProfile, UploadedFile $file, string $type): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename  = Str::uuid()->toString() . '.' . $extension;
        $path      = $file->storeAs('vet-documents', $filename, 'public');

        // Update the primary license document URL
        if ($type === 'license') {
            $vetProfile->update(['license_document_url' => $path]);
        }

        Log::info('Vet document uploaded', [
            'vet_profile_id' => $vetProfile->id,
            'document_type'  => $type,
            'document_path'  => $path,
        ]);

        return $path;
    }

    public function licenseNumberExists(string $licenseNumber): bool
    {
        return VetProfile::where('license_number', $licenseNumber)->exists();
    }

    public function userHasVetProfile(int $userId): bool
    {
        return VetProfile::where('user_id', $userId)->exists();
    }

    /**
     * Get paginated list of vets by status.
     */
    public function getVetsByStatus(string $status, int $perPage = 20): LengthAwarePaginator
    {
        return VetProfile::byStatus($status)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated list of unverified/pending vet profiles.
     */
    public function getUnverifiedVets(int $perPage = 20): LengthAwarePaginator
    {
        return $this->getVetsByStatus('pending', $perPage);
    }

    /**
     * Approve a vet profile.
     *
     * Runs eligibility checks (profile completeness, document verification,
     * status guards) before allowing approval. Creates a persistent
     * VetVerification record with a JSON snapshot of the vet data.
     *
     * @throws \DomainException If vet is not in 'pending' status
     * @throws VetApprovalException If profile is incomplete
     * @throws VetDocumentException If documents are missing
     */
    public function approveVet(VetProfile $vetProfile, User $admin, ?string $notes = null): VetProfile
    {
        return DB::transaction(function () use ($vetProfile, $admin, $notes) {
            // Lock the row to prevent race conditions
            $vetProfile = VetProfile::where('id', $vetProfile->id)->lockForUpdate()->first();

            // Status guards — block dangerous approval states
            if ($vetProfile->isApproved()) {
                throw new \DomainException('Vet is already approved.');
            }
            if ($vetProfile->isRejected()) {
                throw new \DomainException('Vet was previously rejected. A new application is required.');
            }
            if ($vetProfile->isSuspended()) {
                throw new \DomainException('Vet is suspended. Use reactivation instead of approval.');
            }
            if (!$vetProfile->isPending()) {
                throw new \DomainException("Only pending vets can be approved. Current status: {$vetProfile->vet_status}");
            }

            // Profile completeness check
            $missingFields = $this->verificationService->getMissingFields($vetProfile);
            if (count($missingFields) > 0) {
                $this->logVerificationFailure($vetProfile, $admin, 'approval_blocked', 'Profile incomplete', $missingFields);
                throw new VetApprovalException('Vet profile incomplete', $missingFields);
            }

            // Document verification check
            $missingDocs = $this->verificationService->getMissingDocuments($vetProfile);
            if (count($missingDocs) > 0) {
                $this->logVerificationFailure($vetProfile, $admin, 'approval_blocked', 'Documents missing', $missingDocs);
                throw new VetDocumentException('Required documents missing or corrupted', $missingDocs);
            }

            // All clear — approve
            $vetProfile->update([
                'is_verified'      => true,
                'vet_status'       => 'approved',
                'rejection_reason' => null,
                'verified_at'      => now(),
                'verified_by'      => $admin->id,
            ]);

            // Create persistent verification record with snapshot
            $this->verificationService->createVerificationRecord(
                $vetProfile, $admin, 'approved', $notes
            );

            // Audit log
            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'action'         => 'approved',
                'reason'         => $notes,
                'metadata'       => [
                    'license_number' => $vetProfile->license_number,
                    'vet_name'       => $vetProfile->vet_name,
                ],
            ]);

            Log::channel('stack')->info('Vet approved', [
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
            ]);

            return $vetProfile->fresh('user');
        });
    }

    /**
     * Reject a vet profile.
     */
    public function rejectVet(VetProfile $vetProfile, User $admin, string $reason): VetProfile
    {
        return DB::transaction(function () use ($vetProfile, $admin, $reason) {
            $vetProfile = VetProfile::where('id', $vetProfile->id)->lockForUpdate()->first();

            if (!$vetProfile->isPending()) {
                throw new \DomainException("Only pending vets can be rejected. Current status: {$vetProfile->vet_status}");
            }

            $vetProfile->update([
                'is_verified'      => false,
                'vet_status'       => 'rejected',
                'rejection_reason' => $reason,
                'verified_at'      => null,
                'verified_by'      => null,
            ]);

            // Persistent verification record with snapshot
            $this->verificationService->createVerificationRecord(
                $vetProfile, $admin, 'rejected', $reason
            );

            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'action'         => 'rejected',
                'reason'         => $reason,
                'metadata'       => [
                    'license_number' => $vetProfile->license_number,
                    'vet_name'       => $vetProfile->vet_name,
                ],
            ]);

            Log::channel('stack')->info('Vet rejected', [
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'reason'         => $reason,
            ]);

            return $vetProfile->fresh('user');
        });
    }

    /**
     * Suspend an approved vet.
     */
    public function suspendVet(VetProfile $vetProfile, User $admin, string $reason): VetProfile
    {
        return DB::transaction(function () use ($vetProfile, $admin, $reason) {
            $vetProfile = VetProfile::where('id', $vetProfile->id)->lockForUpdate()->first();

            if (!$vetProfile->isApproved()) {
                throw new \DomainException("Only approved vets can be suspended. Current status: {$vetProfile->vet_status}");
            }

            $vetProfile->update([
                'vet_status'       => 'suspended',
                'is_active'        => false,
                'rejection_reason' => $reason,
            ]);

            // Persistent verification record with snapshot
            $this->verificationService->createVerificationRecord(
                $vetProfile, $admin, 'suspended', $reason
            );

            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'action'         => 'suspended',
                'reason'         => $reason,
                'metadata'       => [
                    'license_number' => $vetProfile->license_number,
                    'vet_name'       => $vetProfile->vet_name,
                ],
            ]);

            Log::channel('stack')->info('Vet suspended', [
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'reason'         => $reason,
            ]);

            return $vetProfile->fresh('user');
        });
    }

    /**
     * Reactivate a suspended vet.
     */
    public function reactivateVet(VetProfile $vetProfile, User $admin, ?string $reason = null): VetProfile
    {
        return DB::transaction(function () use ($vetProfile, $admin, $reason) {
            $vetProfile = VetProfile::where('id', $vetProfile->id)->lockForUpdate()->first();

            if (!$vetProfile->isSuspended()) {
                throw new \DomainException("Only suspended vets can be reactivated. Current status: {$vetProfile->vet_status}");
            }

            $vetProfile->update([
                'vet_status'       => 'approved',
                'is_active'        => true,
                'rejection_reason' => null,
            ]);

            // Persistent verification record with snapshot
            $this->verificationService->createVerificationRecord(
                $vetProfile, $admin, 'reactivated', $reason
            );

            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'action'         => 'reactivated',
                'reason'         => $reason,
                'metadata'       => [
                    'license_number' => $vetProfile->license_number,
                    'vet_name'       => $vetProfile->vet_name,
                ],
            ]);

            Log::channel('stack')->info('Vet reactivated', [
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
            ]);

            return $vetProfile->fresh('user');
        });
    }

    /**
     * Get verification history for a vet profile.
     */
    public function getVerificationHistory(int $vetProfileId): \Illuminate\Database\Eloquent\Collection
    {
        return VetVerificationLog::where('vet_profile_id', $vetProfileId)
            ->with('admin:id,name,email')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Log a verification failure (blocked approval attempt).
     */
    private function logVerificationFailure(
        VetProfile $vetProfile,
        User $admin,
        string $failureType,
        array $details
    ): void {
        VetVerificationLog::create([
            'vet_profile_id' => $vetProfile->id,
            'admin_id'       => $admin->id,
            'action'         => 'approval_blocked',
            'reason'         => "Approval blocked: {$failureType}",
            'metadata'       => $details,
        ]);

        Log::channel('stack')->warning('Vet approval blocked', [
            'vet_profile_id' => $vetProfile->id,
            'admin_id'       => $admin->id,
            'failure_type'   => $failureType,
            'details'        => $details,
        ]);
    }

    private function extractCityFromAddress(string $address): string
    {
        $parts = array_map('trim', explode(',', $address));
        return count($parts) >= 2 ? $parts[count($parts) - 2] : $parts[0];
    }
}
