<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\IncidentLog;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use App\Policies\AppointmentPolicy;
use App\Policies\BlogCommentPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\CommunityPostPolicy;
use App\Policies\CommunityReplyPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\PetPolicy;
use App\Policies\SosPolicy;
use App\Policies\VetProfilePolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory as FirebaseFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\AdminMetrics::class,
            \App\Services\AdminMetricsService::class
        );

        // Resilient FirebaseFactory binding. If credentials file is missing or invalid,
        // returns a bare factory — downstream services (FcmNotificationDispatcher,
        // WebRtcProvider) catch \Throwable around actual API calls, so tests and
        // local dev still work without Firebase configured.
        $this->app->singleton(FirebaseFactory::class, function () {
            $factory = new FirebaseFactory();
            $credentialsPath = config('firebase.projects.app.credentials');

            if (!is_string($credentialsPath) || $credentialsPath === '') {
                return $factory;
            }

            $resolvedPath = file_exists($credentialsPath) ? $credentialsPath : base_path($credentialsPath);
            if (!file_exists($resolvedPath)) {
                \Illuminate\Support\Facades\Log::warning('Firebase credentials file not found — running without Firebase', [
                    'configured_path' => $credentialsPath,
                ]);
                return $factory;
            }

            try {
                return $factory->withServiceAccount($resolvedPath);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Firebase service account load failed', [
                    'path' => $resolvedPath,
                    'error' => $e->getMessage(),
                ]);
                return $factory;
            }
        });

        // FCM dispatcher takes a lazy Messaging resolver — closure returns null when
        // Firebase is unavailable, which the dispatcher handles gracefully.
        $this->app->bind(
            \App\Contracts\NotificationDispatcher::class,
            function ($app) {
                return new \App\Services\FcmNotificationDispatcher(function () use ($app) {
                    try {
                        return $app->make(FirebaseFactory::class)->createMessaging();
                    } catch (\Throwable $e) {
                        return null;
                    }
                });
            }
        );

        $this->app->bind(
            \App\Contracts\OtpCodeGenerator::class,
            \App\Services\Otp\RandomOtpCodeGenerator::class
        );

        // WebRTC provider — wraps a SignalingChannel (Firebase Realtime Database in prod).
        // The channel resolver returns null when Firebase is unavailable; the provider
        // falls back to "P2P-only" room metadata so the rest of the consult flow keeps working.
        $this->app->bind(
            \App\Contracts\VideoProviderInterface::class,
            function ($app) {
                return new \App\Services\Video\WebRtcProvider(function () use ($app) {
                    try {
                        $database = $app->make(FirebaseFactory::class)->createDatabase();
                        return new \App\Services\Video\FirebaseSignalingChannel($database);
                    } catch (\Throwable $e) {
                        return null;
                    }
                });
            }
        );

        // Chat broadcaster — pushes consultation messages to Firebase for realtime fan-out.
        // Source of truth is MySQL; this is a side-effect that fails open.
        // Backend chosen by FIREBASE_CHAT_BACKEND env (realtime|firestore). Firestore
        // requires ext-grpc on the host; realtime DB works everywhere kreait does.
        $this->app->bind(
            \App\Contracts\ChatMessageBroadcaster::class,
            function ($app) {
                $backend = config('services.firebase.chat_backend', 'realtime');

                if ($backend === 'firestore') {
                    return new \App\Services\Chat\FirestoreChatBroadcaster(function () use ($app) {
                        try {
                            $client = $app->make(FirebaseFactory::class)->createFirestore()->database();
                            return new \App\Services\Chat\GoogleFirestoreWriter($client);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning(
                                'Firestore unavailable — chat broadcast disabled until ext-grpc is enabled or backend switched',
                                ['error' => $e->getMessage()]
                            );
                            return null;
                        }
                    });
                }

                // Default: Realtime Database
                return new \App\Services\Chat\FirebaseChatBroadcaster(function () use ($app) {
                    try {
                        return $app->make(FirebaseFactory::class)->createDatabase();
                    } catch (\Throwable $e) {
                        return null;
                    }
                });
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Relation::enforceMorphMap([
            'appointment' => Appointment::class,
            'sos_request' => SosRequest::class,
            'user' => User::class,
            'community_post' => CommunityPost::class,
            'community_reply' => CommunityReply::class,
        ]);

        Gate::policy(Pet::class, PetPolicy::class);
        Gate::policy(SosRequest::class, SosPolicy::class);
        Gate::policy(IncidentLog::class, IncidentPolicy::class);
        Gate::policy(VetProfile::class, VetProfilePolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(BlogPost::class, BlogPostPolicy::class);
        Gate::policy(BlogComment::class, BlogCommentPolicy::class);
        Gate::policy(CommunityPost::class, CommunityPostPolicy::class);
        Gate::policy(CommunityReply::class, CommunityReplyPolicy::class);
    }
}
