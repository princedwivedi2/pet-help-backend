<?php

namespace App\Services;

use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetVerificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class VetOnboardingService
{
    /**
     * Register a new veterinarian.
     *
     * Creates a user with role=vet and a linked vet profile.
     * Profile is unverified by default.
     */
    public function registerVet(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create user account with vet role
            $user = User::create([
                'name'     => $data['full_name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'vet',
            ]);

            // Create vet profile linked to user
            $vetProfile = VetProfile::create([
                'user_id'               => $user->id,
                'vet_name'              => $data['full_name'],
                'clinic_name'           => $data['clinic_name'],
                'phone'                 => $data['phone_number'],
                'email'                 => $data['email'],
                'address'               => $data['clinic_address'],
                'city'                  => $this->extractCityFromAddress($data['clinic_address']),
                'latitude'              => $data['latitude'],
                'longitude'             => $data['longitude'],
                'qualifications'        => $data['qualifications'],
                'license_number'        => $data['license_number'],
                'years_of_experience'   => $data['years_of_experience'],
                'accepted_species'      => $data['accepted_species'],
                'services'              => $data['services_offered'],
                'is_verified'           => false,
                'is_active'             => true,
            ]);

            // Issue Sanctum token
            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::channel('stack')->info('Vet registered', [
                'user_id'        => $user->id,
                'vet_profile_id' => $vetProfile->id,
                'license_number' => $data['license_number'],
            ]);

            return [
                'user'        => $user,
                'vet_profile' => $vetProfile,
                'token'       => $token,
            ];
        });
    }

    /**
     * Check if a vet profile already exists for the given license number.
     */
    public function licenseNumberExists(string $licenseNumber): bool
    {
        return VetProfile::where('license_number', $licenseNumber)->exists();
    }

    /**
     * Check if a user already has a vet profile.
     */
    public function userHasVetProfile(int $userId): bool
    {
        return VetProfile::where('user_id', $userId)->exists();
    }

    /**
     * Get paginated list of unverified vet profiles.
     */
    public function getUnverifiedVets(int $perPage = 20): LengthAwarePaginator
    {
        return VetProfile::where('is_verified', false)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Approve a vet profile.
     */
    public function approveVet(VetProfile $vetProfile, User $admin): VetProfile
    {
        return DB::transaction(function () use ($vetProfile, $admin) {
            $vetProfile->update([
                'is_verified'      => true,
                'rejection_reason' => null,
                'verified_at'      => now(),
                'verified_by'      => $admin->id,
            ]);

            VetVerificationLog::create([
                'vet_profile_id' => $vetProfile->id,
                'admin_id'       => $admin->id,
                'action'         => 'approved',
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
            $vetProfile->update([
                'is_verified'      => false,
                'rejection_reason' => $reason,
                'verified_at'      => null,
                'verified_by'      => null,
            ]);

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
     * Extract city from a full address string (best effort).
     */
    private function extractCityFromAddress(string $address): string
    {
        $parts = array_map('trim', explode(',', $address));
        // Assume second-to-last segment is the city, or fallback to first segment
        return count($parts) >= 2 ? $parts[count($parts) - 2] : $parts[0];
    }
}
