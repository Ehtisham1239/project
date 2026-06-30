<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS URL generation in production (behind a load balancer/proxy,
        // APP_URL alone isn't always enough for correct asset/route URLs).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
