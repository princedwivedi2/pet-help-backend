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

        $this->app->singleton(FirebaseFactory::class, function () {
            return (new FirebaseFactory())->withServiceAccount(
                config('firebase.projects.app.credentials') ?? config('firebase.projects.app')
            );
        });

        $this->app->bind(
            \App\Contracts\NotificationDispatcher::class,
            \App\Services\FcmNotificationDispatcher::class
        );

        $this->app->bind(
            \App\Contracts\OtpCodeGenerator::class,
            \App\Services\Otp\RandomOtpCodeGenerator::class
        );

        // Online consultation video provider. Using WebRTC with Firebase signaling.
        // Alternative: TwilioVideoProvider, DailyVideoProvider, etc.
        $this->app->bind(
            \App\Contracts\VideoProviderInterface::class,
            \App\Services\Video\WebRtcProvider::class
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
