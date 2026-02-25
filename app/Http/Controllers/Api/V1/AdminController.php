<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\VetApprovalException;
use App\Exceptions\VetDocumentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\VetApproveRequest;
use App\Http\Requests\Api\V1\Vet\VetRejectRequest;
use App\Http\Requests\Api\V1\Vet\VetSuspendRequest;
use App\Http\Requests\Api\V1\Vet\VerifyVetRequest;
use App\Models\IncidentLog;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use App\Services\AdminMetricsService;
use App\Services\VetOnboardingService;
use App\Services\VetVerificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetOnboardingService $vetOnboardingService,
        private AdminMetricsService $adminMetricsService,
        private VetVerificationService $vetVerificationService,
    ) {}

    /**
     * Escape LIKE wildcard characters in search input.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }

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
            $search = $this->escapeLike($request->search);
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

        // Set role explicitly — not mass-assignable for security
        $user->role = $request->role;
        $user->save();

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

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('emergency_type', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
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

    // ─── Pets Dashboard ─────────────────────────────────────────────

    /**
     * List all pets across all users (admin view).
     *
     * GET /api/v1/admin/pets
     */
    public function pets(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = \App\Models\Pet::with(['user:id,name,email']);

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('species')) {
            $query->where('species', $request->species);
        }

        $pets = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Pets retrieved successfully', [
            'pets' => $pets->items(),
            'pagination' => [
                'current_page' => $pets->currentPage(),
                'last_page'    => $pets->lastPage(),
                'per_page'     => $pets->perPage(),
                'total'        => $pets->total(),
            ],
        ]);
    }

    // ─── Stats ───────────────────────────────────────────────────────

    /**
     * List all appointments (admin view across all users/vets).
     *
     * GET /api/v1/admin/appointments
     */
    public function appointments(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = \App\Models\Appointment::with([
            'user:id,name,email',
            'vetProfile:id,uuid,clinic_name,vet_name,phone',
            'pet:id,name,species',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        $appointments = $query->orderByDesc('scheduled_at')->paginate($perPage);

        return $this->success('Appointments retrieved successfully', [
            'appointments' => $appointments->items(),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
                'per_page'     => $appointments->perPage(),
                'total'        => $appointments->total(),
            ],
        ]);
    }

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

    // ─── Vet Verification & Management ─────────────────────────────

    /**
     * List vets by status (pending, approved, rejected, suspended).
     *
     * GET /api/v1/admin/vets?status=pending
     */
    public function vetsByStatus(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);
        $status = $request->query('status');
        $search = $request->query('search');

        $query = VetProfile::with(['user:id,name,email', 'verifiedByAdmin:id,name']);

        if ($status) {
            $query->byStatus($status);
        }

        if ($search) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('vet_name', 'like', "%{$search}%")
                  ->orWhere('clinic_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        $vets = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Vets retrieved successfully', [
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
     * List unverified (pending) vet profiles.
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
     * Get a single vet profile with full details.
     *
     * GET /api/v1/admin/vets/{uuid}
     */
    public function showVet(string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)
            ->with([
                'user:id,name,email,phone,created_at',
                'verifiedByAdmin:id,name,email',
                'verificationLogs' => function ($q) {
                    $q->with('admin:id,name')->orderByDesc('created_at');
                },
                'availabilities',
            ])
            ->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        // Build document URLs if documents exist
        $documents = [];
        if ($vetProfile->license_document_url) {
            $documents[] = [
                'type' => 'license',
                'path' => $vetProfile->license_document_url,
                'url'  => asset('storage/' . $vetProfile->license_document_url),
            ];
        }

        return $this->success('Vet profile retrieved successfully', [
            'vet_profile' => $vetProfile,
            'documents'   => $documents,
        ]);
    }

    /**
     * Review a vet profile before approval — returns eligibility, missing fields, documents, snapshot.
     *
     * GET /api/v1/admin/vets/{uuid}/review
     */
    public function reviewVet(string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)
            ->with(['user:id,name,email', 'verificationLogs' => function ($q) {
                $q->with('admin:id,name')->orderByDesc('created_at')->limit(10);
            }])
            ->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $reviewData = $this->vetVerificationService->buildReviewData($vetProfile);

        return $this->success('Vet review data retrieved', $reviewData);
    }

    /**
     * Approve a vet profile.
     *
     * PUT /api/v1/admin/vets/{uuid}/approve
     */
    public function approveVet(VetApproveRequest $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        try {
            $vetProfile = $this->vetOnboardingService->approveVet(
                $vetProfile,
                $request->user(),
                $request->notes ?? null
            );
        } catch (VetApprovalException $e) {
            return $this->error($e->getMessage(), [
                'missing_fields' => $e->getMissingFields(),
            ], 422);
        } catch (VetDocumentException $e) {
            return $this->error($e->getMessage(), [
                'missing_documents' => $e->getMissingDocuments(),
            ], 422);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('Vet approved successfully', ['vet_profile' => $vetProfile]);
    }

    /**
     * Reject a vet profile.
     *
     * PUT /api/v1/admin/vets/{uuid}/reject
     */
    public function rejectVet(VetRejectRequest $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        try {
            $vetProfile = $this->vetOnboardingService->rejectVet(
                $vetProfile,
                $request->user(),
                $request->reason
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('Vet rejected successfully', ['vet_profile' => $vetProfile]);
    }

    /**
     * Legacy: Approve or reject a vet profile (combined endpoint).
     *
     * PUT /api/v1/admin/vets/{uuid}/verify
     */
    public function verifyVet(VerifyVetRequest $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $admin = $request->user();

        try {
            if ($request->action === 'approve') {
                $vetProfile = $this->vetOnboardingService->approveVet(
                    $vetProfile, $admin, $request->notes ?? null
                );
                return $this->success('Vet approved successfully', ['vet_profile' => $vetProfile]);
            }

            $vetProfile = $this->vetOnboardingService->rejectVet(
                $vetProfile,
                $admin,
                $request->reason
            );
        } catch (VetApprovalException $e) {
            return $this->error($e->getMessage(), [
                'missing_fields' => $e->getMissingFields(),
            ], 422);
        } catch (VetDocumentException $e) {
            return $this->error($e->getMessage(), [
                'missing_documents' => $e->getMissingDocuments(),
            ], 422);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('Vet rejected', ['vet_profile' => $vetProfile]);
    }

    /**
     * Suspend a vet profile.
     *
     * PUT /api/v1/admin/vets/{uuid}/suspend
     */
    public function suspendVet(VetSuspendRequest $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        try {
            $vetProfile = $this->vetOnboardingService->suspendVet(
                $vetProfile,
                $request->user(),
                $request->reason
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('Vet suspended successfully', ['vet_profile' => $vetProfile]);
    }

    /**
     * Reactivate a suspended vet profile.
     *
     * PUT /api/v1/admin/vets/{uuid}/reactivate
     */
    public function reactivateVet(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        try {
            $vetProfile = $this->vetOnboardingService->reactivateVet(
                $vetProfile,
                $request->user(),
                $request->reason ?? 'Reactivated by admin'
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('Vet reactivated successfully', ['vet_profile' => $vetProfile]);
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

    // ─── Admin Metrics ───────────────────────────────────────────────

    /**
     * Get recent activity for the dashboard.
     *
     * GET /api/v1/admin/recent-activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 10), 50);

        return $this->success('Recent activity retrieved', [
            'activities' => $this->adminMetricsService->getRecentActivity($limit),
        ]);
    }

    /**
     * Get dashboard metrics summary.
     *
     * GET /api/v1/admin/metrics
     */
    public function metrics(): JsonResponse
    {
        return $this->success('Dashboard metrics retrieved', [
            'metrics' => $this->adminMetricsService->getDashboardSummary(),
        ]);
    }

    /**
     * Get time-series metrics for charts.
     *
     * GET /api/v1/admin/metrics/time-series?metric=registrations&period=daily&limit=30
     */
    public function timeSeries(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|in:registrations,sos_requests,appointments,incidents,pets',
            'period' => 'nullable|in:daily,weekly,monthly',
            'limit'  => 'nullable|integer|min:1|max:365',
        ]);

        $data = $this->adminMetricsService->getTimeSeries(
            $request->metric,
            $request->period ?? 'daily',
            (int) ($request->limit ?? 30)
        );

        return $this->success('Time series data retrieved', [
            'metric' => $request->metric,
            'period' => $request->period ?? 'daily',
            'data'   => $data,
        ]);
    }

    /**
     * Get geographic distribution.
     *
     * GET /api/v1/admin/metrics/geo?entity=vets
     */
    public function geoDistribution(Request $request): JsonResponse
    {
        $request->validate([
            'entity' => 'nullable|in:users,vets',
        ]);

        $data = $this->adminMetricsService->getGeoDistribution(
            $request->entity ?? 'vets'
        );

        return $this->success('Geographic distribution retrieved', [
            'entity' => $request->entity ?? 'vets',
            'data'   => $data,
        ]);
    }
}
