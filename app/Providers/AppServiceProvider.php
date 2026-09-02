<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; 


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tvinga HTTPS om vi är bakom en proxy (som ngrok)
        //if ($this->app->environment('production') || env('APP_URL')) {
          //  URL::forceScheme('https');
        //}
    }
}
