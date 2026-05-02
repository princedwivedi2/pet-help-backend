<?php

use App\Jobs\ConsultationNoShowWatchdogJob;
use App\Services\AppointmentService;
use App\Services\SosService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Consultation no-show watchdog: auto-refund instant consults where the vet
// matched but never joined within 10 minutes. Runs every minute (cheap scan).
Schedule::job(new ConsultationNoShowWatchdogJob())
    ->everyMinute()
    ->name('consultations:no-show-watchdog')
    ->onOneServer()
    ->withoutOverlapping();

// SOS Escalation: Check every 2 minutes for pending SOS that need escalation
Schedule::call(function () {
    try {
        app(SosService::class)->autoEscalatePending();
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyTwoMinutes()
    ->name('sos:escalate-pending')
    ->onOneServer()
    ->withoutOverlapping();

// HIGH-06: Auto-expire stale SOS requests every 5 minutes
Schedule::call(function () {
    try {
        app(SosService::class)->expireStale();
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyFiveMinutes()
    ->name('sos:expire-stale')
    ->onOneServer()
    ->withoutOverlapping();

// HIGH-04 FIX: Auto-expire stale appointments every 15 minutes
Schedule::call(function () {
    try {
        app(AppointmentService::class)->expireStale();
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyFifteenMinutes()
    ->name('appointments:expire-stale')
    ->onOneServer()
    ->withoutOverlapping();

// Notify waitlist when slots become available (after appointments cancelled/completed)
Schedule::call(function () {
    try {
        app(AppointmentService::class)->processWaitlistNotifications();
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyFiveMinutes()
    ->name('appointments:process-waitlist')
    ->onOneServer()
    ->withoutOverlapping();

// Pet Reminder Notifications: Send notifications for upcoming pet care reminders
Schedule::call(function () {
    try {
        $upcomingReminders = \App\Models\PetReminder::where('is_completed', false)
            ->where('scheduled_at', '<=', now()->addMinutes(60))
            ->where('scheduled_at', '>', now())
            ->with(['pet', 'user'])
            ->get();

        foreach ($upcomingReminders as $reminder) {
            $user = $reminder->user;
            $notificationKey = 'pets:upcoming-reminder:' . $reminder->id . ':' . $reminder->scheduled_at->timestamp;
            $shouldNotify = Cache::add($notificationKey, true, now()->addMinutes(70));

            if ($shouldNotify && $user && in_array('database', $reminder->notification_methods ?? ['database'])) {
                $user->notify(new \App\Notifications\PetReminderNotification($reminder));
            }
        }
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->hourly()
    ->name('pets:reminder-notifications')
    ->onOneServer()
    ->withoutOverlapping();

// Pet Medication Reminders: Check for medication doses that are due
Schedule::call(function () {
    try {
        $overdueReminders = \App\Models\PetReminder::where('reminder_type', 'medication')
            ->where('is_completed', false)
            ->where('scheduled_at', '<=', now())
            ->with(['pet', 'user', 'medication'])
            ->get();

        foreach ($overdueReminders as $reminder) {
            $user = $reminder->user;
            $windowBucket = now()->format('YmdH') . '-' . (int) floor(now()->minute / 30);
            $notificationKey = 'pets:overdue-medication:' . $reminder->id . ':' . $windowBucket;
            $shouldNotify = Cache::add($notificationKey, true, now()->addMinutes(31));

            if ($shouldNotify && $user) {
                $user->notify(new \App\Notifications\OverdueMedicationNotification($reminder));
            }
        }
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyThirtyMinutes()
    ->name('pets:medication-reminders')
    ->onOneServer()
    ->withoutOverlapping();

// Pet Document Expiry Alerts: Notify about expiring documents (vaccinations, insurance, etc.)
Schedule::call(function () {
    try {
        $expiringDocuments = \App\Models\PetDocument::whereBetween('expiry_date', [
            now()->addDays(7),  // 7 days from now
            now()->addDays(30)  // 30 days from now
        ])
        ->with(['pet', 'user'])
        ->get();

        foreach ($expiringDocuments as $document) {
            $user = $document->user;
            $notificationKey = 'pets:document-expiry:' . $document->id . ':' . now()->toDateString();
            $shouldNotify = Cache::add($notificationKey, true, now()->endOfDay());

            if ($shouldNotify && $user) {
                $user->notify(new \App\Notifications\DocumentExpiryNotification($document));
            }
        }
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->dailyAt('09:00')
    ->name('pets:document-expiry-alerts')
    ->onOneServer()
    ->withoutOverlapping();

// Production cron entry (server): * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
