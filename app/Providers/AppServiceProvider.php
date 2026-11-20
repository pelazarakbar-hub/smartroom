<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Waktu relatif seperti "2 jam yang lalu" tampil dalam bahasa Indonesia.
        Carbon::setLocale('id');
    }
}
