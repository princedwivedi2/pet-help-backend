<?php

use App\Services\AppointmentService;
use App\Services\SosService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// HIGH-06: Auto-expire stale SOS requests every 5 minutes
Schedule::call(fn () => app(SosService::class)->expireStale())
    ->everyFiveMinutes()
    ->name('sos:expire-stale')
    ->withoutOverlapping();

// HIGH-04 FIX: Auto-expire stale appointments every 15 minutes
Schedule::call(fn () => app(AppointmentService::class)->expireStale())
    ->everyFifteenMinutes()
    ->name('appointments:expire-stale')
    ->withoutOverlapping();
