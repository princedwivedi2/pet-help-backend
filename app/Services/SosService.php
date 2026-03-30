<?php

namespace App\Services;

use App\Models\SosRequest;
use App\Models\IncidentLog;
use App\Models\User;
use App\Notifications\SosAlertNotification;
use App\Notifications\SosStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class SosService
{
    /**
     * Standardized SOS status transitions.
     * Primary statuses use sos_ prefix. Legacy names are accepted for backward compatibility.
     */
    private const VALID_TRANSITIONS = [
        // ── New standardized statuses ──
        'sos_pending' => ['sos_accepted', 'sos_cancelled', 'expired'],
        'sos_accepted' => ['vet_on_the_way', 'sos_in_progress', 'sos_cancelled'],
        'vet_on_the_way' => ['arrived', 'sos_cancelled'],
        'arrived' => ['sos_in_progress', 'sos_cancelled'],
        'sos_in_progress' => ['sos_completed', 'sos_cancelled'],
        'sos_completed' => [],
        'sos_cancelled' => [],
        'expired' => [],
        // ── Legacy status backward compat (map to new names) ──
        'pending' => ['sos_accepted', 'acknowledged', 'sos_cancelled', 'cancelled', 'expired'],
        'acknowledged' => ['vet_on_the_way', 'sos_in_progress', 'in_progress', 'completed', 'sos_completed', 'sos_cancelled', 'cancelled'],
        'in_progress' => ['sos_completed', 'completed', 'sos_cancelled', 'cancelled'],
        'treatment_in_progress' => ['sos_completed', 'completed', 'sos_cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private VetSearchService $vetSearchService,
        private AuditService $auditService
    ) {
    }

    public function createSos(User $user, array $data): SosRequest
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock user's SOS rows to prevent duplicate active SOS and rate-limit bypass
            $activeCount = SosRequest::where('user_id', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled', 'sos_completed', 'sos_cancelled', 'expired'])
                ->lockForUpdate()
                ->count();

            if ($activeCount > 0) {
                throw new \DomainException('You already have an active SOS request. Please complete or cancel it first.');
            }

            $recentCount = SosRequest::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentCount >= 5) {
                throw new \DomainException('You can only create 5 SOS requests per hour. Please wait before creating another.');
            }

            $sosRequest = SosRequest::create([
                'user_id' => $user->id,
                'pet_id' => $data['pet_id'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'address' => $data['address'] ?? null,
                'description' => $data['description'],
                'emergency_type' => $data['emergency_type'] ?? 'other',
                'status' => 'sos_pending',
                'auto_expire_at' => now()->addMinutes(30), // Auto-expire after 30 minutes
            ]);

            // Create incident log automatically
            IncidentLog::create([
                'user_id' => $user->id,
                'pet_id' => $data['pet_id'] ?? null,
                'sos_request_id' => $sosRequest->id,
                'title' => 'SOS Emergency: ' . ucfirst($sosRequest->emergency_type),
                'description' => $data['description'],
                'incident_type' => 'emergency',
                'status' => 'open',
                'incident_date' => now()->toDateString(),
            ]);

            Log::channel('stack')->info('SOS created', [
                'sos_uuid' => $sosRequest->uuid,
                'user_id' => $user->id,
                'pet_id' => $data['pet_id'] ?? null,
                'emergency_type' => $sosRequest->emergency_type,
                'lat' => $data['latitude'],
                'lng' => $data['longitude'],
            ]);

            // Audit
            $this->auditService->logStatusChange($user->id, SosRequest::class, $sosRequest->id, null, 'sos_pending');

            // Dispatch notification to SOS owner (confirmation)
            try {
                $user->notify(new SosAlertNotification($sosRequest));
            } catch (\Throwable $e) {
                report($e);
            }

            return $sosRequest->load(['pet', 'user']);
        });
    }

    /**
     * Find and notify nearest emergency-available vets.
     */
    public function findNearestVetsStub(float $latitude, float $longitude, SosRequest $sosRequest, int $limit = 5): array
    {
        $nearbyVets = $this->vetSearchService->getNearbyVets(
            $latitude,
            $longitude,
            radiusKm: 25,
            emergencyOnly: true,
            limit: $limit
        );

        // CRIT-04: Send actual SOS data to each nearby vet (not generic stub)
        foreach ($nearbyVets as $vet) {
            try {
                if ($vet->user) {
                    $vet->user->notify(new SosAlertNotification($sosRequest));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'vets_notified' => $nearbyVets->count(),
            'vets' => $nearbyVets->map(fn($vet) => [
                'uuid' => $vet->uuid,
                'clinic_name' => $vet->clinic_name,
                'distance_km' => round($vet->distance_km, 2),
            ])->toArray(),
        ];
    }

    /**
     * Main status update method with full lifecycle support.
     */
    public function updateStatus(SosRequest $sosRequest, string $status, ?string $notes = null, ?array $extra = []): SosRequest
    {
        return DB::transaction(function () use ($sosRequest, $status, $notes, $extra) {
            $sosRequest = SosRequest::where('id', $sosRequest->id)->lockForUpdate()->first();

            $current = $sosRequest->status;
            $allowed = self::VALID_TRANSITIONS[$current] ?? [];

            if (!in_array($status, $allowed, true)) {
                throw new \DomainException(
                    "Cannot transition SOS from '{$current}' to '{$status}'. Allowed: " . (implode(', ', $allowed) ?: 'none')
                );
            }

            $previousStatus = $sosRequest->status;
            $updateData = ['status' => $status];

            // Status-specific data
            switch ($status) {
                case 'sos_accepted':
                case 'acknowledged':
                    $updateData['acknowledged_at'] = now();
                    if (in_array($previousStatus, ['sos_pending', 'pending'])) {
                        $updateData['response_time_seconds'] = now()->diffInSeconds($sosRequest->created_at);
                    }
                    if (isset($extra['vet_profile_id'])) {
                        $updateData['assigned_vet_id'] = $extra['vet_profile_id'];
                    }
                    if (isset($extra['response_type'])) {
                        $updateData['response_type'] = $extra['response_type'];
                    }
                    if (isset($extra['estimated_arrival_at'])) {
                        $updateData['estimated_arrival_at'] = $extra['estimated_arrival_at'];
                    }
                    break;

                case 'vet_on_the_way':
                    $updateData['vet_departed_at'] = now();
                    if (isset($extra['vet_latitude'])) {
                        $updateData['vet_latitude'] = $extra['vet_latitude'];
                        $updateData['vet_longitude'] = $extra['vet_longitude'];
                    }
                    break;

                case 'arrived':
                    $updateData['vet_arrived_at'] = now();
                    if (isset($extra['vet_latitude'])) {
                        $updateData['vet_latitude'] = $extra['vet_latitude'];
                        $updateData['vet_longitude'] = $extra['vet_longitude'];
                    }
                    if ($sosRequest->vet_departed_at) {
                        $updateData['arrival_time_seconds'] = now()->diffInSeconds($sosRequest->vet_departed_at);
                    }
                    break;

                case 'treatment_in_progress':
                case 'in_progress':
                case 'sos_in_progress':
                    $updateData['treatment_started_at'] = now();
                    break;

                case 'sos_completed':
                case 'completed':
                    $updateData['completed_at'] = now();
                    if ($notes) {
                        $updateData['resolution_notes'] = $notes;
                    }
                    if (isset($extra['emergency_charge'])) {
                        $updateData['emergency_charge'] = $extra['emergency_charge'];
                    }
                    if (isset($extra['distance_travelled_km'])) {
                        $updateData['distance_travelled_km'] = $extra['distance_travelled_km'];
                    }
                    break;

                case 'sos_cancelled':
                case 'cancelled':
                    $updateData['completed_at'] = now();
                    if ($notes) {
                        $updateData['resolution_notes'] = $notes;
                    }
                    break;

                case 'expired':
                    $updateData['completed_at'] = now();
                    $updateData['resolution_notes'] = 'Auto-expired — no vet accepted within the time limit.';
                    break;
            }

            $sosRequest->update($updateData);

            // Update related incident log status
            if ($sosRequest->incidentLog) {
                $incidentStatus = match ($status) {
                    'completed', 'sos_completed' => 'resolved',
                    'cancelled', 'sos_cancelled', 'expired' => 'resolved',
                    'in_progress', 'treatment_in_progress' => 'in_treatment',
                    default => 'open',
                };
                $sosRequest->incidentLog->update(['status' => $incidentStatus]);
            }

            // Audit
            $this->auditService->logStatusChange(auth()->id(), SosRequest::class, $sosRequest->id, $previousStatus, $status);

            Log::channel('stack')->info('SOS status updated', [
                'sos_uuid' => $sosRequest->uuid,
                'user_id' => $sosRequest->user_id,
                'from' => $previousStatus,
                'to' => $status,
                'has_notes' => !empty($notes),
            ]);

            // Notify SOS owner of status change
            try {
                $sosRequest->user?->notify(new SosStatusNotification($sosRequest, $previousStatus));
            } catch (\Throwable $e) {
                report($e);
            }

            return $sosRequest->fresh(['pet', 'assignedVet']);
        });
    }

    /**
     * Vet accepts the SOS and tracks location.
     */
    public function vetAccept(SosRequest $sosRequest, int $vetProfileId, ?float $lat = null, ?float $lng = null): SosRequest
    {
        return $this->updateStatus($sosRequest, 'sos_accepted', null, [
            'vet_profile_id' => $vetProfileId,
            'vet_latitude' => $lat,
            'vet_longitude' => $lng,
        ]);
    }

    /**
     * Update vet's live location while en route.
     */
    public function updateVetLocation(SosRequest $sosRequest, float $lat, float $lng): SosRequest
    {
        $sosRequest->update([
            'vet_latitude' => $lat,
            'vet_longitude' => $lng,
        ]);
        return $sosRequest->fresh();
    }

    /**
     * Expire SOS requests that passed their auto_expire_at timestamp.
     */
    public function expireStale(): int
    {
        $expired = SosRequest::whereIn('status', ['pending', 'sos_pending'])
            ->whereNotNull('auto_expire_at')
            ->where('auto_expire_at', '<=', now())
            ->get();

        foreach ($expired as $sos) {
            try {
                /** @var SosRequest $sos */
                $this->updateStatus($sos, 'expired', null);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $expired->count();
    }

    public function getActiveSosForUser(User $user): ?SosRequest
    {
        return $user->sosRequests()
            ->active()
            ->with(['pet', 'assignedVet'])
            ->latest()
            ->first();
    }

    /**
     * Get ALL active SOS requests (for admins).
     */
    public function getAllActiveSos()
    {
        return SosRequest::active()
            ->with(['user:id,name,phone', 'pet:id,name,species', 'assignedVet'])
            ->latest()
            ->get();
    }

    /**
     * Get active SOS requests within a radius of the vet's location.
     */
    public function getActiveSosNearby(float $latitude, float $longitude, float $radiusKm = 25): \Illuminate\Database\Eloquent\Collection
    {
        // HIGH-01 FIX: Sanitize coordinates to prevent SQL injection edge cases
        $lat = number_format($latitude, 8, '.', '');
        $lng = number_format($longitude, 8, '.', '');

        $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(latitude)) * cos(radians(longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(latitude))))";

        return SosRequest::active()
            ->with(['user:id,name,phone', 'pet:id,name,species', 'assignedVet'])
            ->select('sos_requests.*')
            ->selectRaw("{$haversine} AS distance_km")
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->get();
    }

    public function getUserSosCountLastHour(User $user): int
    {
        return $user->sosRequests()
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
}
