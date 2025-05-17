<?php

namespace App\Providers;

use App\Commands\CommandInvoker;
use App\Decorators\EmailContent\EmailContentProcessorFactory;
use App\Decorators\EmailContent\EmailContentProcessorInterface;
use App\Services\CampaignCommandService;
use App\Services\MailService;
use App\Services\MailServiceInterface;
use App\Strategies\EmailTracking\DefaultTrackingStrategy;
use App\Strategies\EmailTracking\TrackingStrategy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind MailServiceInterface to the concrete MailService implementation
        $this->app->bind(MailServiceInterface::class, MailService::class);

        // Bind TrackingStrategy to DefaultTrackingStrategy
        $this->app->bind(TrackingStrategy::class, DefaultTrackingStrategy::class);

        // Bind EmailContentProcessorInterface to the factory-created instance
        $this->app->bind(EmailContentProcessorInterface::class, function ($app) {
            return EmailContentProcessorFactory::createFullProcessor(
                $app->make(TrackingStrategy::class)
            );
        });

        // Register Command Invoker as a singleton
        $this->app->singleton(CommandInvoker::class, function ($app) {
            return new CommandInvoker;
        });

        // Register Campaign Command Service
        $this->app->bind(CampaignCommandService::class, function ($app) {
            return new CampaignCommandService(
                $app->make(CommandInvoker::class)
            );
        });
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
