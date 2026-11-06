<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programación de sincronización automática NAS
Schedule::command('nas:sync --all')
    ->hourly()
    ->withoutOverlapping(180)
    ->runInBackground();
