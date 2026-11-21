<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bersihkan data sensor lama setiap hari (butuh scheduler berjalan: php artisan schedule:run tiap menit).
Schedule::command('smartroom:prune-readings --days=30')->dailyAt('03:00');
