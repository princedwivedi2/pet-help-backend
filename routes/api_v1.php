<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\GuideController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\SosController;
use App\Http\Controllers\Api\V1\VetController;
use App\Http\Controllers\Api\V1\VetOnboardingController;
use Illuminate\Support\Facades\Route;

// ─── Auth ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// ─── Vet Registration ───────────────────────────────────────────────
Route::middleware('throttle:3,1')->group(function () {
    Route::post('vet/register', [VetOnboardingController::class, 'register']);
});

// ─── Public: Guides & Vets ──────────────────────────────────────────
Route::get('emergency-categories', [GuideController::class, 'categories']);
Route::get('guides', [GuideController::class, 'index']);
Route::get('guides/{id}', [GuideController::class, 'show']);
Route::get('vets', [VetController::class, 'index']);
Route::get('vets/{uuid}', [VetController::class, 'show']);

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

// ─── Authenticated ──────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Pets
    Route::apiResource('pets', PetController::class);

    // SOS
    Route::prefix('sos')->group(function () {
        Route::post('/', [SosController::class, 'store']);
        Route::get('/active', [SosController::class, 'active']);
        Route::put('/{uuid}/status', [SosController::class, 'updateStatus']);
    });

    // Incidents
    Route::get('incidents', [IncidentController::class, 'index']);
    Route::get('incidents/{uuid}', [IncidentController::class, 'show']);

    // Vet Profile
    Route::get('vet/profile', [VetOnboardingController::class, 'profile']);

    // Blog: Comments & Likes
    Route::post('blog/posts/{uuid}/comments', [BlogController::class, 'storeComment']);
    Route::post('blog/posts/{uuid}/like', [BlogController::class, 'toggleLike']);

    // Community: Posts, Replies, Votes, Reports
    Route::post('community/posts', [CommunityController::class, 'storePost']);
    Route::delete('community/posts/{uuid}', [CommunityController::class, 'destroyPost']);
    Route::post('community/posts/{uuid}/replies', [CommunityController::class, 'storeReply']);
    Route::delete('community/replies/{uuid}', [CommunityController::class, 'destroyReply']);
    Route::post('community/votes', [CommunityController::class, 'vote']);
    Route::post('community/reports', [CommunityController::class, 'report']);
});

// ─── Admin ──────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('stats', [AdminController::class, 'stats']);
    Route::get('users', [AdminController::class, 'users']);
    Route::put('users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::get('sos', [AdminController::class, 'sosRequests']);
    Route::get('incidents', [AdminController::class, 'incidents']);

    // Vet Verification
    Route::get('vets/unverified', [AdminController::class, 'unverifiedVets']);
    Route::put('vets/{uuid}/verify', [AdminController::class, 'verifyVet']);
    Route::get('vets/{uuid}/history', [AdminController::class, 'vetVerificationHistory']);

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
});

