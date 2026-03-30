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
use App\Services\PaymentService;
use App\Services\VetProfileCompletionService;
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
        private PaymentService $paymentService,
        private VetProfileCompletionService $vetProfileCompletionService,
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

        // MED-05 FIX: Prevent promoting to admin without explicit confirmation
        if ($request->role === 'admin' && $user->role !== 'admin') {
            if (!$request->boolean('confirm_admin_promotion')) {
                return $this->validationError('Admin promotion requires confirmation', [
                    'role' => ['Promoting to admin role requires confirm_admin_promotion=true parameter.'],
                ]);
            }
        }

        // MED-05 FIX: Prevent demoting another admin
        if ($user->role === 'admin' && $request->role !== 'admin') {
            return $this->forbidden('Cannot demote another admin. Contact super-admin for this action.');
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
        $paidPayments = \App\Models\Payment::paid();

        return $this->success('Dashboard stats retrieved', [
            'stats' => [
                'total_users' => User::count(),
                'total_vets' => VetProfile::count(),
                'pending_vet_approvals' => VetProfile::byStatus('pending')->count(),
                'total_pets' => \App\Models\Pet::count(),
                'active_sos' => SosRequest::active()->count(),
                'total_sos' => SosRequest::count(),
                'total_incidents' => IncidentLog::count(),
                'appointments_today' => \App\Models\Appointment::whereDate('scheduled_at', today())->count(),
                'sos_by_status' => SosRequest::query()
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'unverified_vets' => VetProfile::unverified()->count(),
                'total_blog_posts' => \App\Models\BlogPost::count(),
                'total_community_topics' => \App\Models\CommunityTopic::count(),
                'total_community_posts' => \App\Models\CommunityPost::count(),
                'pending_reports' => \App\Models\CommunityReport::pending()->count(),
                'platform_revenue' => (int) $paidPayments->sum('platform_fee'),
                'gross_revenue' => (int) $paidPayments->sum('amount'),
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
        $status = $request->query('verification_status', $request->query('status'));
        $search = $request->query('search');
        $city = $request->query('city');
        $specialization = $request->query('specialization');
        $experience = $request->query('experience');

        $query = VetProfile::with(['user:id,name,email']);

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

        if ($city) {
            $escapedCity = $this->escapeLike($city);
            $query->where(function ($q) use ($escapedCity) {
                $q->where('city', 'like', "%{$escapedCity}%")
                    ->orWhere('address', 'like', "%{$escapedCity}%");
            });
        }

        if ($specialization) {
            $escapedSpec = $this->escapeLike($specialization);
            $query->where(function ($q) use ($escapedSpec) {
                $q->where('specialization', 'like', "%{$escapedSpec}%")
                    ->orWhere('qualifications', 'like', "%{$escapedSpec}%");
            });
        }

        if ($experience !== null && $experience !== '') {
            $query->where('years_of_experience', '>=', (int) $experience);
        }

        $vets = $query->orderByDesc('created_at')->paginate($perPage);

        $vetItems = collect($vets->items())->map(function (VetProfile $vet) {
            $completion = $this->vetProfileCompletionService->buildCompletionPayload($vet);

            return array_merge($vet->toArray(), [
                'profile_completion_percentage' => $completion['completion_percentage'],
                'profile_missing_fields' => $completion['missing_fields'],
            ]);
        })->values()->all();

        return $this->success('Vets retrieved successfully', [
            'vets' => $vetItems,
            'counts' => [
                'pending' => VetProfile::byStatus('pending')->count(),
                'approved' => VetProfile::byStatus('approved')->count(),
                'rejected' => VetProfile::byStatus('rejected')->count(),
                'suspended' => VetProfile::byStatus('suspended')->count(),
            ],
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
                'user:id,name,email,phone,created_at,last_login_at',
                'verifications' => function ($q) {
                    $q->with('admin:id,name')->orderByDesc('created_at');
                },
                'availabilities',
            ])
            ->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $documents = [];
        $documentFields = [
            'license' => $vetProfile->license_document_url,
            'degree_certificate' => $vetProfile->degree_certificate_url,
            'government_id' => $vetProfile->government_id_url,
        ];
        foreach ($documentFields as $type => $path) {
            if ($path) {
                $documents[] = [
                    'type' => $type,
                    'path' => $path,
                    'url' => str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/')),
                ];
            }
        }

        $appointmentsTotal = $vetProfile->appointments()->count();
        $consultationsTotal = $vetProfile->appointments()->where('status', 'completed')->count();
        $appointmentsToday = $vetProfile->appointments()->whereDate('scheduled_at', today())->count();
        $sosResponses = $vetProfile->sosRequests()->count();
        $sosCompleted = $vetProfile->sosRequests()->whereIn('status', ['completed', 'sos_completed'])->count();
        $revenueGenerated = (int) $vetProfile->payments()->paid()->sum('vet_payout_amount');

        $center = $this->majorCityCenterForState($vetProfile->state);
        $distanceFromCityCenter = null;
        if ($center && $vetProfile->latitude !== null && $vetProfile->longitude !== null) {
            $distanceFromCityCenter = round($this->haversineDistanceKm(
                (float) $center['lat'],
                (float) $center['lng'],
                (float) $vetProfile->latitude,
                (float) $vetProfile->longitude
            ), 2);
        }

        $completion = $this->vetProfileCompletionService->buildCompletionPayload($vetProfile);

        return $this->success('Vet profile retrieved successfully', [
            'vet_profile' => $vetProfile,
            'profile_completion_percentage' => $completion['completion_percentage'],
            'profile_missing_fields' => $completion['missing_fields'],
            'documents'   => $documents,
            'inspection' => [
                'vet_status' => $vetProfile->vet_status,
                'verification_status' => $vetProfile->verification_status ?? $vetProfile->vet_status,
                'last_login' => $vetProfile->user?->last_login_at,
                'account_created_at' => $vetProfile->user?->created_at,
                'profile_updated_at' => $vetProfile->updated_at,
                'location' => [
                    'address' => $vetProfile->address,
                    'city' => $vetProfile->city,
                    'state' => $vetProfile->state,
                    'latitude' => $vetProfile->latitude,
                    'longitude' => $vetProfile->longitude,
                    'major_city_center' => $center,
                    'distance_from_city_center_km' => $distanceFromCityCenter,
                    'map_url' => ($vetProfile->latitude !== null && $vetProfile->longitude !== null)
                        ? 'https://maps.google.com/?q=' . $vetProfile->latitude . ',' . $vetProfile->longitude
                        : null,
                ],
                'stats' => [
                    'appointments_total' => $appointmentsTotal,
                    'appointments_today' => $appointmentsToday,
                    'total_consultations' => $consultationsTotal,
                    'ratings_average' => $vetProfile->avg_rating,
                    'ratings_count' => $vetProfile->total_reviews,
                    'sos_responses' => $sosResponses,
                    'sos_completed' => $sosCompleted,
                    'revenue_generated' => $revenueGenerated,
                ],
                'profile_completion_percentage' => $completion['completion_percentage'],
                'missing_fields' => $completion['missing_fields'],
            ],
        ]);
    }

    /**
     * Request additional information from a vet profile under review.
     */
    public function requestMoreInfo(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $vetProfile = VetProfile::where('uuid', $uuid)->first();
        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        try {
            $vetProfile = $this->vetOnboardingService->requestMoreInfo(
                $vetProfile,
                $request->user(),
                $request->reason
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), [], 409);
        }

        return $this->success('More information requested from vet', ['vet_profile' => $vetProfile]);
    }

    private function majorCityCenterForState(?string $state): ?array
    {
        if (!$state) {
            return null;
        }

        $map = [
            'NY' => ['name' => 'New York City', 'lat' => 40.7128, 'lng' => -74.0060],
            'CA' => ['name' => 'Los Angeles', 'lat' => 34.0522, 'lng' => -118.2437],
            'Maharashtra' => ['name' => 'Mumbai', 'lat' => 19.0760, 'lng' => 72.8777],
            'Delhi' => ['name' => 'New Delhi', 'lat' => 28.6139, 'lng' => 77.2090],
            'Karnataka' => ['name' => 'Bengaluru', 'lat' => 12.9716, 'lng' => 77.5946],
        ];

        return $map[$state] ?? null;
    }

    private function haversineDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Review a vet profile before approval — returns eligibility, missing fields, documents, snapshot.
     *
     * GET /api/v1/admin/vets/{uuid}/review
     */
    public function reviewVet(string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)
            ->with(['user:id,name,email', 'verifications' => function ($q) {
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

    // ─── Revenue & Payments ──────────────────────────────────────────

    /**
     * Get revenue stats.
     *
     * GET /api/v1/admin/revenue
     */
    public function revenue(Request $request): JsonResponse
    {
        $stats = $this->paymentService->getRevenueStats();

        return $this->success('Revenue stats retrieved', ['revenue' => $stats]);
    }

    /**
     * Get pending vet payouts.
     *
     * GET /api/v1/admin/payouts/pending
     */
    public function pendingPayouts(): JsonResponse
    {
        $payouts = $this->paymentService->getPendingPayouts();

        return $this->success('Pending payouts retrieved', ['payouts' => $payouts]);
    }

    /**
     * List all payments with filters.
     *
     * GET /api/v1/admin/payments
     */
    public function payments(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = \App\Models\Payment::with([
            'user:id,name,email',
            'vetProfile:id,uuid,clinic_name,vet_name',
        ]);

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('payment_model')) {
            $query->where('payment_model', $request->payment_model);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $payments = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Payments retrieved successfully', [
            'payments' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    // ─── Audit Logs ──────────────────────────────────────────────────

    /**
     * List audit logs with filters.
     *
     * GET /api/v1/admin/audit-logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $query = \App\Models\AuditLog::with(['user:id,name,email']);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Audit logs retrieved', [
            'audit_logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    // ─── Ad Banners ──────────────────────────────────────────────────

    /**
     * List all ad banners.
     * GET /api/v1/admin/ad-banners
     */
    public function adBanners(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);
        $query = \App\Models\AdBanner::query();

        if ($request->filled('position')) {
            $query->forPosition($request->position);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $banners = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success('Ad banners retrieved', [
            'ad_banners' => $banners->items(),
            'pagination' => [
                'current_page' => $banners->currentPage(),
                'last_page'    => $banners->lastPage(),
                'per_page'     => $banners->perPage(),
                'total'        => $banners->total(),
            ],
        ]);
    }

    /**
     * Create an ad banner.
     * POST /api/v1/admin/ad-banners
     */
    public function storeAdBanner(Request $request): JsonResponse
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'image_url'  => 'required|string|max:500',
            'link_url'   => 'nullable|string|max:500',
            'position'   => 'required|in:home_top,home_bottom,search_results,vet_profile',
            'priority'   => 'nullable|integer|min:0|max:100',
            'starts_at'  => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $banner = \App\Models\AdBanner::create($request->only([
            'title', 'image_url', 'link_url', 'position', 'priority', 'starts_at', 'expires_at',
        ]));

        return $this->created('Ad banner created', ['ad_banner' => $banner]);
    }

    /**
     * Update an ad banner.
     * PUT /api/v1/admin/ad-banners/{uuid}
     */
    public function updateAdBanner(Request $request, string $uuid): JsonResponse
    {
        $banner = \App\Models\AdBanner::where('uuid', $uuid)->first();

        if (!$banner) {
            return $this->notFound('Ad banner not found');
        }

        $request->validate([
            'title'      => 'sometimes|string|max:255',
            'image_url'  => 'sometimes|string|max:500',
            'link_url'   => 'nullable|string|max:500',
            'position'   => 'sometimes|in:home_top,home_bottom,search_results,vet_profile',
            'priority'   => 'nullable|integer|min:0|max:100',
            'is_active'  => 'sometimes|boolean',
            'starts_at'  => 'nullable|date',
            'ends_at'    => 'nullable|date',
        ]);

        $banner->update($request->only([
            'title', 'image_url', 'link_url', 'position', 'priority', 'is_active', 'starts_at', 'ends_at',
        ]));

        return $this->success('Ad banner updated', ['ad_banner' => $banner]);
    }

    /**
     * Delete an ad banner.
     * DELETE /api/v1/admin/ad-banners/{uuid}
     */
    public function destroyAdBanner(string $uuid): JsonResponse
    {
        $banner = \App\Models\AdBanner::where('uuid', $uuid)->first();

        if (!$banner) {
            return $this->notFound('Ad banner not found');
        }

        $banner->delete();

        return $this->success('Ad banner deleted');
    }

    // ─── Subscriptions ───────────────────────────────────────────────

    /**
     * List subscription plans.
     * GET /api/v1/admin/subscription-plans
     */
    public function subscriptionPlans(): JsonResponse
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('price')->get();

        return $this->success('Subscription plans retrieved', ['plans' => $plans]);
    }

    /**
     * Create a subscription plan.
     * POST /api/v1/admin/subscription-plans
     */
    public function storeSubscriptionPlan(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:user,vet',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features'      => 'nullable|array',
            'description'   => 'nullable|string|max:1000',
        ]);

        $plan = \App\Models\SubscriptionPlan::create($request->only([
            'name', 'type', 'price', 'duration_days', 'features', 'description',
        ]));

        return $this->created('Plan created', ['plan' => $plan]);
    }

    /**
     * Update a subscription plan.
     * PUT /api/v1/admin/subscription-plans/{id}
     */
    public function updateSubscriptionPlan(Request $request, int $id): JsonResponse
    {
        $plan = \App\Models\SubscriptionPlan::find($id);

        if (!$plan) {
            return $this->notFound('Subscription plan not found');
        }

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'type'          => 'sometimes|in:user,vet',
            'price'         => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'features'      => 'nullable|array',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'sometimes|boolean',
        ]);

        $plan->update($request->only([
            'name', 'type', 'price', 'duration_days', 'features', 'description', 'is_active',
        ]));

        return $this->success('Plan updated', ['plan' => $plan]);
    }

    /**
     * Delete a subscription plan.
     * DELETE /api/v1/admin/subscription-plans/{id}
     */
    public function destroySubscriptionPlan(int $id): JsonResponse
    {
        $plan = \App\Models\SubscriptionPlan::find($id);

        if (!$plan) {
            return $this->notFound('Subscription plan not found');
        }

        $plan->update(['is_active' => false]);

        return $this->success('Subscription plan deactivated');
    }

    // ─── Reviews ─────────────────────────────────────────────────────

    /**
     * List flagged reviews.
     * GET /api/v1/admin/reviews/flagged
     */
    public function flaggedReviews(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $reviews = \App\Models\Review::where('is_flagged', true)
            ->with(['user:id,name', 'vetProfile:id,uuid,vet_name,clinic_name', 'appointment:id,uuid'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success('Flagged reviews retrieved', [
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * Resolve a flagged review (unflag and keep).
     * PUT /api/v1/admin/reviews/{uuid}/resolve
     */
    public function resolveReview(string $uuid): JsonResponse
    {
        $review = \App\Models\Review::where('uuid', $uuid)->first();

        if (!$review) {
            return $this->notFound('Review not found');
        }

        if (!$review->is_flagged) {
            return $this->error('Review is not flagged', null, 422);
        }

        $review->update([
            'is_flagged'  => false,
            'flag_reason' => null,
        ]);

        return $this->success('Review resolved (unflagged)', ['review' => $review]);
    }

    /**
     * Dismiss a flagged review (unflag with admin note).
     * PUT /api/v1/admin/reviews/{uuid}/dismiss
     */
    public function dismissReview(Request $request, string $uuid): JsonResponse
    {
        $review = \App\Models\Review::where('uuid', $uuid)->first();

        if (!$review) {
            return $this->notFound('Review not found');
        }

        if (!$review->is_flagged) {
            return $this->error('Review is not flagged', null, 422);
        }

        $review->update([
            'is_flagged'  => false,
            'flag_reason' => null,
        ]);

        return $this->success('Review flag dismissed', ['review' => $review]);
    }

    /**
     * Delete a flagged review (soft-delete).
     * DELETE /api/v1/admin/reviews/{uuid}
     */
    public function destroyReview(string $uuid): JsonResponse
    {
        $review = \App\Models\Review::where('uuid', $uuid)->first();

        if (!$review) {
            return $this->notFound('Review not found');
        }

        $vetProfileId = $review->vet_profile_id;
        $review->delete();

        // Recalculate vet rating after deletion
        $remaining = \App\Models\Review::where('vet_profile_id', $vetProfileId)
            ->whereNull('deleted_at')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        \App\Models\VetProfile::where('id', $vetProfileId)->update([
            'avg_rating'    => $remaining->avg_rating ?? 0,
            'total_reviews' => $remaining->total_reviews ?? 0,
        ]);

        return $this->success('Review deleted');
    }
}
