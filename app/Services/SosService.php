<?php

namespace App\Services;

use App\Models\SosRequest;
use App\Models\IncidentLog;
use App\Models\User;
use App\Notifications\SosAlertNotification;
use App\Notifications\SosStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SosService
{
    public function __construct(
        private VetSearchService $vetSearchService
    ) {}

    public function createSos(User $user, array $data): SosRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $sosRequest = SosRequest::create([
                'user_id' => $user->id,
                'pet_id' => $data['pet_id'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'address' => $data['address'] ?? null,
                'description' => $data['description'],
                'emergency_type' => $data['emergency_type'] ?? 'other',
                'status' => 'pending',
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

            // Dispatch notification to SOS owner (confirmation)
            try {
                $user->notify(new SosAlertNotification($sosRequest));
            } catch (\Throwable $e) {
                report($e);
            }

            return $sosRequest->load(['pet', 'user']);
        });
    }

    public function findNearestVetsStub(float $latitude, float $longitude, int $limit = 5): array
    {
        // Placeholder: In production, this would integrate with
        // notification services to alert nearby vets
        $nearbyVets = $this->vetSearchService->getNearbyVets(
            $latitude,
            $longitude,
            radiusKm: 25,
            emergencyOnly: true,
            limit: $limit
        );

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
     * Valid SOS status transitions.
     * Prevents backward transitions (e.g., completed → pending).
     */
    private const VALID_TRANSITIONS = [
        'pending'      => ['acknowledged', 'cancelled'],
        'acknowledged' => ['in_progress', 'cancelled'],
        'in_progress'  => ['completed', 'cancelled'],
        'completed'    => [],
        'cancelled'    => [],
    ];

    public function updateStatus(SosRequest $sosRequest, string $status, ?string $notes = null): SosRequest
    {
        $current = $sosRequest->status;
        $allowed = self::VALID_TRANSITIONS[$current] ?? [];

        if (!in_array($status, $allowed, true)) {
            throw new \DomainException(
                "Cannot transition SOS from '{$current}' to '{$status}'. Allowed: " . (implode(', ', $allowed) ?: 'none')
            );
        }

        return DB::transaction(function () use ($sosRequest, $status, $notes) {
            $previousStatus = $sosRequest->status;
            $updateData = ['status' => $status];

            if ($status === 'completed') {
                $updateData['completed_at'] = now();
                if ($notes) {
                    $updateData['resolution_notes'] = $notes;
                }
            } elseif ($status === 'acknowledged') {
                $updateData['acknowledged_at'] = now();
            }

            $sosRequest->update($updateData);

            // Update related incident log status
            if ($sosRequest->incidentLog) {
                $incidentStatus = match ($status) {
                    'completed' => 'resolved',
                    'cancelled' => 'resolved',
                    'in_progress' => 'in_treatment',
                    default => 'open',
                };
                $sosRequest->incidentLog->update(['status' => $incidentStatus]);
            }

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

    public function getActiveSosForUser(User $user): ?SosRequest
    {
        return $user->sosRequests()
            ->active()
            ->with(['pet', 'assignedVet'])
            ->latest()
            ->first();
    }

    public function getUserSosCountLastHour(User $user): int
    {
        return $user->sosRequests()
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }
}
