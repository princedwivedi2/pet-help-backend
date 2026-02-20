<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\VerifyVetRequest;
use App\Models\IncidentLog;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use App\Services\VetOnboardingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetOnboardingService $vetOnboardingService
    ) {}

    // ─── Users ───────────────────────────────────────────────────────

    /**
     * List all users with pagination.
     */
    public function users(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = User::query()->withCount(['pets', 'sosRequests', 'incidentLogs']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Users retrieved successfully', [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Update a user's role.
     */
    public function updateUserRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:user,vet,admin',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->notFound('User not found');
        }

        // Prevent self-demotion
        if ($user->id === $request->user()->id) {
            return $this->validationError('Cannot change own role', [
                'role' => ['You cannot change your own role.'],
            ]);
        }

        $user->update(['role' => $request->role]);

        return $this->success('User role updated successfully', ['user' => $user]);
    }

    // ─── SOS Dashboard ──────────────────────────────────────────────

    /**
     * List all SOS requests (admin dashboard view).
     */
    public function sosRequests(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = SosRequest::with(['user:id,name,email', 'pet:id,name,species'])
            ->withTrashed();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $sos = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('SOS requests retrieved successfully', [
            'sos_requests' => $sos->items(),
            'pagination' => [
                'current_page' => $sos->currentPage(),
                'last_page' => $sos->lastPage(),
                'per_page' => $sos->perPage(),
                'total' => $sos->total(),
            ],
        ]);
    }

    // ─── Incidents Dashboard ─────────────────────────────────────────

    /**
     * List all incident logs (admin dashboard view).
     */
    public function incidents(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = IncidentLog::with(['user:id,name,email', 'pet:id,name,species']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }

        $incidents = $query->orderByDesc('incident_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);

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

    // ─── Stats ───────────────────────────────────────────────────────

    /**
     * Dashboard summary stats.
     */
    public function stats(): JsonResponse
    {
        return $this->success('Dashboard stats retrieved', [
            'stats' => [
                'total_users' => User::count(),
                'total_pets' => \App\Models\Pet::count(),
                'active_sos' => SosRequest::active()->count(),
                'total_sos' => SosRequest::count(),
                'total_incidents' => IncidentLog::count(),
                'sos_by_status' => SosRequest::query()
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'unverified_vets' => VetProfile::unverified()->count(),
                'total_blog_posts' => \App\Models\BlogPost::count(),
                'total_community_topics' => \App\Models\CommunityTopic::count(),
                'total_community_posts' => \App\Models\CommunityPost::count(),
                'pending_reports' => \App\Models\CommunityReport::pending()->count(),
            ],
        ]);
    }

    // ─── Vet Verification ────────────────────────────────────────────

    /**
     * List unverified vet profiles.
     *
     * GET /api/v1/admin/vets/unverified
     */
    public function unverifiedVets(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $vets = $this->vetOnboardingService->getUnverifiedVets($perPage);

        return $this->success('Unverified vets retrieved successfully', [
            'vets' => $vets->items(),
            'pagination' => [
                'current_page' => $vets->currentPage(),
                'last_page'    => $vets->lastPage(),
                'per_page'     => $vets->perPage(),
                'total'        => $vets->total(),
            ],
        ]);
    }

    /**
     * Approve or reject a vet profile.
     *
     * PUT /api/v1/admin/vets/{uuid}/verify
     *
     * Body: { "action": "approve" } or { "action": "reject", "reason": "..." }
     */
    public function verifyVet(VerifyVetRequest $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $admin = $request->user();

        if ($request->action === 'approve') {
            $vetProfile = $this->vetOnboardingService->approveVet($vetProfile, $admin);
            return $this->success('Vet approved successfully', ['vet_profile' => $vetProfile]);
        }

        $vetProfile = $this->vetOnboardingService->rejectVet(
            $vetProfile,
            $admin,
            $request->reason
        );

        return $this->success('Vet rejected', ['vet_profile' => $vetProfile]);
    }

    /**
     * Get verification history for a vet.
     *
     * GET /api/v1/admin/vets/{uuid}/history
     */
    public function vetVerificationHistory(string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $history = $this->vetOnboardingService->getVerificationHistory($vetProfile->id);

        return $this->success('Verification history retrieved', [
            'vet_profile' => $vetProfile,
            'history'     => $history,
        ]);
    }
}
