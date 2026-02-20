<?php

namespace App\Services;

use App\Models\IncidentLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IncidentService
{
    public function getUserIncidents(
        User $user,
        ?int $petId = null,
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = IncidentLog::forUser($user->id)
            ->with(['pet', 'sosRequest', 'vetProfile']);

        if ($petId) {
            $query->forPet($petId);
        }

        if ($status) {
            $query->byStatus($status);
        }

        if ($fromDate || $toDate) {
            $query->dateRange($fromDate, $toDate);
        }

        return $query->orderByDesc('incident_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findForUser(User $user, string $uuid): ?IncidentLog
    {
        return IncidentLog::forUser($user->id)
            ->where('uuid', $uuid)
            ->with(['pet', 'sosRequest', 'vetProfile'])
            ->first();
    }
}
