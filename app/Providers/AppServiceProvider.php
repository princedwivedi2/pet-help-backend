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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

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
