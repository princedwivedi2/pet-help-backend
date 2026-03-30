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
Schedule::call(function () {
    try {
        app(SosService::class)->expireStale();
    } catch (\Throwable $e) {
        report($e);
    }
})
    ->everyFiveMinutes()
    ->onOneServer()
    ->name('sos:expire-stale')
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
    ->withoutOverlapping();
