<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\GuideController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\PetMedicalRecordController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SosController;
use App\Http\Controllers\Api\V1\VetController;
use App\Http\Controllers\Api\V1\VetOnboardingController;
use App\Http\Controllers\Api\V1\VisitRecordController;
use Illuminate\Support\Facades\Route;

// ─── Health Check ───────────────────────────────────────────────────
Route::get('health', [HealthController::class, 'index']);
Route::get('health/detailed', [HealthController::class, 'detailed']);

// ─── Auth ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Public: throttled auth endpoints
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Email verification (no verified middleware here, obviously)
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Authenticated auth endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:3,1');
        Route::put('change-password', [AuthController::class, 'changePassword']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::delete('account', [AuthController::class, 'deleteAccount']);
        Route::post('device-token', [AuthController::class, 'registerDeviceToken']);
    });
});

// ─── Public: Payment Webhook (no auth — HMAC verified inside handler) ──
Route::post('payments/webhook', [PaymentController::class, 'webhook']);

// ─── Vet Registration & Application ─────────────────────────────────
Route::middleware('throttle:3,10')->group(function () {
    Route::post('vet/apply', [VetOnboardingController::class, 'apply']);
    Route::post('vet/register', [VetOnboardingController::class, 'register']);
});

// ─── Public: Guides & Vets ──────────────────────────────────────────
Route::get('emergency-categories', [GuideController::class, 'categories']);
Route::get('guides', [GuideController::class, 'index']);
Route::get('guides/{id}', [GuideController::class, 'show']);
Route::get('vets', [VetController::class, 'index']);
Route::get('vets/{uuid}', [VetController::class, 'show']);

// ─── Public: Reviews ────────────────────────────────────────────────
Route::get('reviews/vet/{uuid}', [ReviewController::class, 'forVet']);

// ─── Public: Subscription Plans ─────────────────────────────────────
Route::get('subscription-plans', function () {
    return response()->json([
        'success' => true,
        'message' => 'Subscription plans retrieved',
        'data' => ['plans' => \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('price')->get()],
    ]);
});

// ─── Public: Active Ad Banners ──────────────────────────────────────
Route::get('ad-banners', function (\Illuminate\Http\Request $request) {
    $position = $request->query('position');
    $query = \App\Models\AdBanner::active();
    if ($position) $query->forPosition($position);
    return response()->json([
        'success' => true,
        'message' => 'Ad banners retrieved',
        'data' => ['ad_banners' => $query->orderByDesc('priority')->get()],
    ]);
});

// ─── Public: Blog ───────────────────────────────────────────────────
Route::prefix('blog')->group(function () {
    Route::get('categories', [BlogController::class, 'categories']);
    Route::get('posts', [BlogController::class, 'posts']);
    Route::get('posts/{uuid}', [BlogController::class, 'showPost']);
    Route::get('tags', [BlogController::class, 'tags']);
});

// ─── Public: Community ──────────────────────────────────────────────
Route::prefix('community')->group(function () {
    Route::get('topics', [CommunityController::class, 'topics']);
    Route::get('posts', [CommunityController::class, 'posts']);
    Route::get('posts/{uuid}', [CommunityController::class, 'showPost']);
    Route::get('posts/{uuid}/replies', [CommunityController::class, 'postReplies']);
});

// ─── Authenticated (verified email required) ────────────────────────
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Pets (user-scoped via controller/policy) - rate limited
    Route::middleware('throttle:60,1')->group(function () {
        Route::apiResource('pets', PetController::class);
    });

    // Pet Management Features - rate limited
    Route::middleware('throttle:30,1')->prefix('pets/{pet}')->group(function () {
        // Pet Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'dashboard']);

        // Pet Notes
        Route::prefix('notes')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'noteIndex']);
            Route::post('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'noteStore']);
            Route::get('/{note}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'noteShow']);
            Route::put('/{note}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'noteUpdate']);
            Route::delete('/{note}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'noteDestroy']);
        });

        // Pet Reminders
        Route::prefix('reminders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'reminderIndex']);
            Route::post('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'reminderStore']);
            Route::put('/{reminder}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'reminderUpdate']);
            Route::delete('/{reminder}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'reminderDestroy']);
            Route::post('/{reminder}/complete', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'reminderComplete']);
        });

        // Pet Documents
        Route::prefix('documents')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'documentIndex']);
            Route::post('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'documentStore']);
            Route::put('/{document}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'documentUpdate']);
            Route::delete('/{document}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'documentDestroy']);
            Route::get('/{document}/download', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'documentDownload']);
        });

        // Pet Medications  
        Route::prefix('medications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationIndex']);
            Route::post('/', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationStore']);
            Route::put('/{medication}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationUpdate']);
            Route::delete('/{medication}', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationDestroy']);
            Route::post('/{medication}/discontinue', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationDiscontinue']);
            Route::post('/{medication}/log', [\App\Http\Controllers\Api\V1\PetManagementController::class, 'medicationLog']);
        });
    });

    // Pet-scoped sub-resources
    Route::prefix('pets/{petId}')->group(function () {
        // Medical Records (full CRUD)
        Route::get('medical-records', [PetMedicalRecordController::class, 'index']);
        Route::post('medical-records', [PetMedicalRecordController::class, 'store']);
        Route::get('medical-records/{uuid}', [PetMedicalRecordController::class, 'show']);
        Route::put('medical-records/{uuid}', [PetMedicalRecordController::class, 'update']);
        Route::delete('medical-records/{uuid}', [PetMedicalRecordController::class, 'destroy']);

        // Pet appointment & visit-record history
        Route::get('appointments', [PetController::class, 'appointments']);
        Route::get('visit-records', [PetController::class, 'visitRecords']);
        // Pet-scoped incident history
        Route::get('incidents', [IncidentController::class, 'petIncidents']);
    });

    // SOS — users create; users + vets update status - rate limited
    Route::middleware('throttle:10,1')->prefix('sos')->group(function () {
        Route::post('/', [SosController::class, 'store']);
        Route::get('/active', [SosController::class, 'active']);
        Route::put('/{uuid}/status', [SosController::class, 'updateStatus']);
        Route::put('/{uuid}/location', [SosController::class, 'updateLocation']);
    });

    // Incidents (user-scoped via controller)
    Route::get('incidents', [IncidentController::class, 'index']);
    Route::get('incidents/{uuid}', [IncidentController::class, 'show']);

    // Appointments — shared routes (users book, vets manage) - rate limited
    Route::middleware('throttle:30,1')->prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/slots/{vet_uuid}', [AppointmentController::class, 'availableSlots']);
        Route::get('/vet', [AppointmentController::class, 'vetIndex'])->middleware('role:vet');
        Route::get('/{uuid}', [AppointmentController::class, 'show']);
        Route::patch('/{uuid}/accept', [AppointmentController::class, 'accept'])->middleware('role:vet');
        Route::patch('/{uuid}/reject', [AppointmentController::class, 'reject'])->middleware('role:vet');
        Route::patch('/{uuid}/start', [AppointmentController::class, 'start'])->middleware('role:vet');
        Route::patch('/{uuid}/complete', [AppointmentController::class, 'complete'])->middleware('role:vet');
        Route::patch('/{uuid}/cancel', [AppointmentController::class, 'cancel']);
        Route::put('/{uuid}/status', [AppointmentController::class, 'updateStatus']);
        Route::put('/{uuid}/end-visit', [AppointmentController::class, 'endVisit']);
        Route::post('/{uuid}/reschedule', [AppointmentController::class, 'reschedule']);
    });

    // Waitlist
    Route::middleware('throttle:20,1')->prefix('waitlist')->group(function () {
        Route::get('/', [AppointmentController::class, 'waitlistIndex']);
        Route::post('/', [AppointmentController::class, 'joinWaitlist']);
        Route::delete('/{uuid}', [AppointmentController::class, 'leaveWaitlist']);
    });

    // Payments - rate limited
    Route::middleware('throttle:20,1')->prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/create-order', [PaymentController::class, 'createOrder']);
        Route::post('/verify', [PaymentController::class, 'verify']);
        Route::post('/offline', [PaymentController::class, 'recordOffline']);
        Route::get('/wallet', [PaymentController::class, 'wallet']);
        Route::get('/{uuid}', [PaymentController::class, 'show']);
        Route::post('/{uuid}/refund', [PaymentController::class, 'refund']);
    });

    // Reviews - rate limited
    Route::middleware('throttle:10,1')->prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{uuid}/reply', [ReviewController::class, 'reply']);
        Route::put('/{uuid}/flag', [ReviewController::class, 'flag']);
    });

    // Visit Records
    Route::prefix('visit-records')->group(function () {
        Route::post('/', [VisitRecordController::class, 'store']);
        Route::put('/{uuid}', [VisitRecordController::class, 'update']);
        Route::post('/{uuid}/prescription', [VisitRecordController::class, 'uploadPrescription']);
        Route::post('/{uuid}/images', [VisitRecordController::class, 'uploadImages']);
        Route::get('/appointment/{uuid}', [VisitRecordController::class, 'forAppointment']);
        Route::get('/sos/{uuid}', [VisitRecordController::class, 'forSos']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    });

    // Blog: Comments & Likes (rate-limited)
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('blog/posts/{uuid}/comments', [BlogController::class, 'storeComment']);
        Route::post('blog/posts/{uuid}/like', [BlogController::class, 'toggleLike']);
    });

    // Community: Posts, Replies, Votes, Reports (rate-limited)
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('community/posts', [CommunityController::class, 'storePost']);
        Route::delete('community/posts/{uuid}', [CommunityController::class, 'destroyPost']);
        Route::post('community/posts/{uuid}/replies', [CommunityController::class, 'storeReply']);
        Route::delete('community/replies/{uuid}', [CommunityController::class, 'destroyReply']);
    });

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('community/votes', [CommunityController::class, 'vote']);
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('community/reports', [CommunityController::class, 'report']);
    });

    // ─── Chatbot / AI Pet Assistant ──────────────────────────────────
    Route::prefix('chatbot/sessions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'store']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'show']);
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'destroy']);
        // Rate-limited message send: 20 messages per minute per user
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('/{uuid}/messages', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'sendMessage']);
        });
        // GET is intentionally outside the throttle group — reading history is cheap and not rate-limited
        Route::get('/{uuid}/messages', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'messages']);
    });
});

// ─── Vet-only routes (require vet role) ─────────────────────────────
Route::middleware(['auth:sanctum', 'verified', 'role:vet'])->group(function () {
    Route::get('vet/profile', [VetOnboardingController::class, 'profile']);
    Route::put('vet/profile', [VetOnboardingController::class, 'updateProfile']);
    Route::post('vet/profile', [VetOnboardingController::class, 'updateProfile']);
    Route::post('vet/documents', [VetOnboardingController::class, 'uploadDocument']);
    Route::get('vet/documents/{type}', [VetOnboardingController::class, 'viewDocument']);
    Route::put('vet/status', [VetOnboardingController::class, 'updateStatus']);
    Route::get('vet/availabilities', [VetOnboardingController::class, 'availabilities']);
    Route::post('vet/availabilities', [VetOnboardingController::class, 'storeAvailability']);
    Route::put('vet/availabilities/{id}', [VetOnboardingController::class, 'updateAvailability']);
    Route::delete('vet/availabilities/{id}', [VetOnboardingController::class, 'destroyAvailability']);
    Route::post('vet/wallet/payout-request', [PaymentController::class, 'payoutRequest']);
});

// ─── Admin ──────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('stats', [AdminController::class, 'stats']);
    Route::get('users', [AdminController::class, 'users']);
    Route::put('users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::get('sos', [AdminController::class, 'sosRequests']);
    Route::get('incidents', [AdminController::class, 'incidents']);
    Route::get('incidents/{uuid}', [IncidentController::class, 'adminShow']);

    // Appointments (admin view — all appointments across all users)
    Route::get('appointments', [AdminController::class, 'appointments']);

    // Pets (admin view — all pets across all users)
    Route::get('pets', [AdminController::class, 'pets']);

    // Vet Verification & Management
    Route::get('vets', [AdminController::class, 'vetsByStatus']);
    Route::get('vets/unverified', [AdminController::class, 'unverifiedVets']);
    Route::get('vets/{uuid}', [AdminController::class, 'showVet']);
    Route::get('vets/{uuid}/review', [AdminController::class, 'reviewVet']);
    Route::put('vets/{uuid}/approve', [AdminController::class, 'approveVet']);
    Route::patch('vets/{uuid}/approve', [AdminController::class, 'approveVet']);
    Route::put('vets/{uuid}/reject', [AdminController::class, 'rejectVet']);
    Route::patch('vets/{uuid}/reject', [AdminController::class, 'rejectVet']);
    Route::put('vets/{uuid}/verify', [AdminController::class, 'verifyVet']);
    Route::put('vets/{uuid}/suspend', [AdminController::class, 'suspendVet']);
    Route::patch('vets/{uuid}/suspend', [AdminController::class, 'suspendVet']);
    Route::put('vets/{uuid}/reactivate', [AdminController::class, 'reactivateVet']);
    Route::patch('vets/{uuid}/reactivate', [AdminController::class, 'reactivateVet']);
    Route::put('vets/{uuid}/request-info', [AdminController::class, 'requestMoreInfo']);
    Route::patch('vets/{uuid}/request-info', [AdminController::class, 'requestMoreInfo']);
    Route::get('vets/{uuid}/history', [AdminController::class, 'vetVerificationHistory']);

    // Admin Metrics
    Route::get('metrics', [AdminController::class, 'metrics']);
    Route::get('metrics/time-series', [AdminController::class, 'timeSeries']);
    Route::get('metrics/geo', [AdminController::class, 'geoDistribution']);
    Route::get('recent-activity', [AdminController::class, 'recentActivity']);

    // Revenue & Payments
    Route::get('revenue', [AdminController::class, 'revenue']);
    Route::get('payments', [AdminController::class, 'payments']);
    Route::get('payouts/pending', [AdminController::class, 'pendingPayouts']);

    // Audit Logs
    Route::get('audit-logs', [AdminController::class, 'auditLogs']);

    // Ad Banners
    Route::get('ad-banners', [AdminController::class, 'adBanners']);
    Route::post('ad-banners', [AdminController::class, 'storeAdBanner']);
    Route::put('ad-banners/{uuid}', [AdminController::class, 'updateAdBanner']);
    Route::delete('ad-banners/{uuid}', [AdminController::class, 'destroyAdBanner']);

    // Subscription Plans
    Route::get('subscription-plans', [AdminController::class, 'subscriptionPlans']);
    Route::post('subscription-plans', [AdminController::class, 'storeSubscriptionPlan']);
    Route::put('subscription-plans/{id}', [AdminController::class, 'updateSubscriptionPlan']);
    Route::delete('subscription-plans/{id}', [AdminController::class, 'destroySubscriptionPlan']);

    // Reviews
    Route::get('reviews/flagged', [AdminController::class, 'flaggedReviews']);
    Route::put('reviews/{uuid}/resolve', [AdminController::class, 'resolveReview']);
    Route::put('reviews/{uuid}/dismiss', [AdminController::class, 'dismissReview']);
    Route::delete('reviews/{uuid}', [AdminController::class, 'destroyReview']);

    // Blog Admin
    Route::prefix('blog')->group(function () {
        Route::get('categories', [BlogController::class, 'adminCategories']);
        Route::post('categories', [BlogController::class, 'storeCategory']);
        Route::put('categories/{uuid}', [BlogController::class, 'updateCategory']);
        Route::delete('categories/{uuid}', [BlogController::class, 'destroyCategory']);

        Route::get('posts', [BlogController::class, 'adminPosts']);
        Route::post('posts', [BlogController::class, 'storePost']);
        Route::get('posts/{uuid}', [BlogController::class, 'adminShowPost']);
        Route::put('posts/{uuid}', [BlogController::class, 'updatePost']);
        Route::delete('posts/{uuid}', [BlogController::class, 'destroyPost']);
        Route::put('posts/{uuid}/toggle-publish', [BlogController::class, 'togglePublish']);

        Route::get('posts/{uuid}/comments', [BlogController::class, 'adminPostComments']);
        Route::put('comments/{uuid}/approve', [BlogController::class, 'approveComment']);
        Route::delete('comments/{uuid}', [BlogController::class, 'destroyComment']);

        Route::get('tags', [BlogController::class, 'adminTags']);
        Route::post('tags', [BlogController::class, 'storeTag']);
    });

    // Community Admin
    Route::prefix('community')->group(function () {
        Route::post('topics', [CommunityController::class, 'storeTopic']);
        Route::put('topics/{uuid}', [CommunityController::class, 'updateTopic']);

        Route::get('posts', [CommunityController::class, 'adminPosts']);
        Route::put('posts/{uuid}/lock', [CommunityController::class, 'toggleLock']);
        Route::put('posts/{uuid}/toggle-visibility', [CommunityController::class, 'toggleVisibility']);
        Route::delete('posts/{uuid}', [CommunityController::class, 'adminDestroyPost']);
        Route::delete('replies/{uuid}', [CommunityController::class, 'adminDestroyReply']);

        Route::get('reports', [CommunityController::class, 'reports']);
        Route::put('reports/{uuid}', [CommunityController::class, 'reviewReport']);
    });

    // Emergency Guides Admin
    Route::prefix('guides')->group(function () {
        Route::get('categories', [GuideController::class, 'adminCategories']);
        Route::post('categories', [GuideController::class, 'storeCategory']);
        Route::put('categories/{id}', [GuideController::class, 'updateCategory']);
        Route::delete('categories/{id}', [GuideController::class, 'destroyCategory']);

        Route::get('/', [GuideController::class, 'adminIndex']);
        Route::post('/', [GuideController::class, 'storeGuide']);
        Route::put('{id}', [GuideController::class, 'updateGuide']);
        Route::delete('{id}', [GuideController::class, 'destroyGuide']);
    });
});

