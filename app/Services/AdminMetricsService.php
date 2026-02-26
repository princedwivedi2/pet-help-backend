<?php

namespace App\Services;

use App\Contracts\AdminMetrics;
use App\Models\Appointment;
use App\Models\BlogPost;
use App\Models\CommunityPost;
use App\Models\IncidentLog;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Support\Facades\DB;

class AdminMetricsService implements AdminMetrics
{
    /**
     * Get summary dashboard stats.
     */
    public function getDashboardSummary(): array
    {
        return [
            // Users
            'total_users'         => User::count(),
            'users_by_role'       => User::query()
                ->selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role'),
            'new_users_today'     => User::whereDate('created_at', today())->count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),

            // Pets
            'total_pets' => Pet::count(),

            // Vets
            'total_vets'             => VetProfile::count(),
            'pending_vet_approvals'  => VetProfile::byStatus('pending')->count(),
            'approved_vets'          => VetProfile::byStatus('approved')->count(),
            'suspended_vets'         => VetProfile::byStatus('suspended')->count(),

            // SOS
            'active_sos'    => SosRequest::active()->count(),
            'total_sos'     => SosRequest::count(),
            'sos_by_status' => SosRequest::query()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),

            // Incidents
            'total_incidents' => IncidentLog::count(),

            // Appointments
            'total_appointments'     => Appointment::count(),
            'pending_appointments'   => Appointment::byStatus('pending')->count(),
            'confirmed_appointments' => Appointment::byStatus('confirmed')->count(),
            'appointments_today'     => Appointment::whereDate('scheduled_at', today())->count(),

            // Blog
            'total_blog_posts'     => BlogPost::count(),
            'published_blog_posts' => BlogPost::where('status', 'published')->count(),

            // Community
            'total_community_posts' => CommunityPost::count(),
            'pending_reports'       => \App\Models\CommunityReport::pending()->count(),
        ];
    }

    /**
     * Get time-series data for charts.
     */
    public function getTimeSeries(string $metric, string $period = 'daily', int $limit = 30): array
    {
        $model = match ($metric) {
            'registrations' => User::class,
            'sos_requests'  => SosRequest::class,
            'appointments'  => Appointment::class,
            'incidents'     => IncidentLog::class,
            'pets'          => Pet::class,
            default         => User::class,
        };

        $dateColumn = match ($metric) {
            'appointments' => 'scheduled_at',
            'incidents'    => 'incident_date',
            default        => 'created_at',
        };

        $dateFormat = match ($period) {
            'daily'   => '%Y-%m-%d',
            'weekly'  => '%x-W%v',
            'monthly' => '%Y-%m',
            default   => '%Y-%m-%d',
        };

        return $model::query()
            ->selectRaw("DATE_FORMAT({$dateColumn}, ?) as date, COUNT(*) as count", [$dateFormat])
            ->where($dateColumn, '>=', match ($period) {
                'daily'   => now()->subDays($limit),
                'weekly'  => now()->subWeeks($limit),
                'monthly' => now()->subMonths($limit),
            })
            ->groupBy('date')
            ->orderBy('date')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent activity for the dashboard.
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $activities = collect();

        // Recent user registrations
        User::latest()->take($limit)->get()->each(function ($user) use (&$activities) {
            $activities->push([
                'action' => 'New user registered',
                'actor'  => $user->name,
                'time'   => $user->created_at,
                'type'   => 'info',
            ]);
        });

        // Recent SOS requests
        SosRequest::with('user:id,name')->latest()->take($limit)->get()->each(function ($sos) use (&$activities) {
            $activities->push([
                'action' => 'SOS request created',
                'actor'  => $sos->user->name ?? 'Unknown',
                'time'   => $sos->created_at,
                'type'   => 'danger',
            ]);
        });

        // Recent vet approvals
        VetProfile::with('user:id,name')->byStatus('approved')->latest('updated_at')->take($limit)->get()->each(function ($vet) use (&$activities) {
            $activities->push([
                'action' => 'Vet profile approved',
                'actor'  => $vet->user->name ?? 'Unknown',
                'time'   => $vet->updated_at,
                'type'   => 'success',
            ]);
        });

        // Recent appointments
        Appointment::with('user:id,name')->latest()->take($limit)->get()->each(function ($appt) use (&$activities) {
            $activities->push([
                'action' => 'Appointment booked',
                'actor'  => $appt->user->name ?? 'Unknown',
                'time'   => $appt->created_at,
                'type'   => 'info',
            ]);
        });

        // Recent blog posts
        BlogPost::with('author:id,name')->where('status', 'published')->latest('published_at')->take($limit)->get()->each(function ($post) use (&$activities) {
            $activities->push([
                'action' => 'Blog post published',
                'actor'  => $post->author->name ?? 'Admin',
                'time'   => $post->published_at ?? $post->created_at,
                'type'   => 'success',
            ]);
        });

        // Recent community reports
        CommunityPost::with('user:id,name')->latest()->take($limit)->get()->each(function ($report) use (&$activities) {
            $activities->push([
                'action' => 'Community post created',
                'actor'  => $report->user->name ?? 'Unknown',
                'time'   => $report->created_at,
                'type'   => 'warning',
            ]);
        });

        // Sort all by time descending and take limit
        return $activities->sortByDesc('time')->take($limit)->values()->map(function ($item) {
            $item['time'] = \Carbon\Carbon::parse($item['time'])->diffForHumans();
            return $item;
        })->toArray();
    }

    /**
     * Get geographic distribution of users/vets.
     * Note: city column was removed — extract city-like substring from address.
     */
    public function getGeoDistribution(string $entity = 'users'): array
    {
        if ($entity === 'vets') {
            return VetProfile::query()
                ->selectRaw("TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', -2), ',', 1)) AS city, COUNT(*) as count")
                ->whereNotNull('address')
                ->where('address', '!=', '')
                ->groupByRaw("TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', -2), ',', 1))")
                ->orderByDesc('count')
                ->limit(20)
                ->get()
                ->toArray();
        }

        // For users, we don't have a city column — return empty until user profiles have location
        return [];
    }
}
