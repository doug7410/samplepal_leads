<?php

namespace App\Providers;

use App\Services\MailService;
use App\Services\SesMailService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind MailService as a substitute for SesMailService
        $this->app->bind(SesMailService::class, MailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS when the original request is HTTPS
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            URL::forceScheme('https');
        }
    }
}
