<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Incident\IndexIncidentRequest;
use App\Models\IncidentLog;
use App\Models\Pet;
use App\Services\IncidentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private IncidentService $incidentService
    ) {}

    public function index(IndexIncidentRequest $request): JsonResponse
    {
        $user = $request->user();

        // Validate pet belongs to user if pet_id provided
        if ($request->filled('pet_id')) {
            $pet = $user->pets()->find($request->pet_id);
            if (!$pet) {
                return $this->validationError('Pet not found', [
                    'pet_id' => ['Pet not found or does not belong to you.'],
                ]);
            }
        }

        $incidents = $this->incidentService->getUserIncidents(
            user: $user,
            petId: $request->pet_id,
            status: $request->status,
            fromDate: $request->from_date,
            toDate: $request->to_date,
            perPage: $request->per_page ?? 15
        );

        return $this->success('Incidents retrieved successfully', [
            'incidents' => $incidents->items(),
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'per_page' => $incidents->perPage(),
                'total' => $incidents->total(),
            ],
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $incident = $this->incidentService->findForUser($request->user(), $uuid);

        if (!$incident) {
            return $this->notFound('Incident not found', [
                'incident' => ['Incident not found or does not belong to you.'],
            ]);
        }

        return $this->success('Incident retrieved successfully', ['incident' => $incident]);
    }

    /**
     * Pet-scoped incident history.
     * GET /api/v1/pets/{petId}/incidents
     */
    public function petIncidents(Request $request, int $petId): JsonResponse
    {
        $user = $request->user();
        $pet  = $user->pets()->find($petId);

        if (!$pet) {
            return $this->notFound('Pet not found');
        }

        $incidents = $this->incidentService->getUserIncidents(
            user: $user,
            petId: $pet->id,
            status: $request->query('status'),
            fromDate: $request->query('from_date'),
            toDate: $request->query('to_date'),
            perPage: (int) $request->query('per_page', 15)
        );

        return $this->success('Pet incidents retrieved', [
            'incidents' => $incidents->items(),
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page'    => $incidents->lastPage(),
                'per_page'     => $incidents->perPage(),
                'total'        => $incidents->total(),
            ],
        ]);
    }

    /**
     * Admin: full incident detail with all relations.
     * GET /api/v1/admin/incidents/{uuid}
     */
    public function adminShow(string $uuid): JsonResponse
    {
        $incident = IncidentLog::where('uuid', $uuid)
            ->with(['user', 'pet', 'sosRequest', 'vetProfile'])
            ->first();

        if (!$incident) {
            return $this->notFound('Incident not found');
        }

        return $this->success('Incident retrieved successfully', ['incident' => $incident]);
    }
}
