<?php

namespace App\Providers;

use App\Commands\CommandInvoker;
use App\Decorators\EmailContent\EmailContentProcessorFactory;
use App\Decorators\EmailContent\EmailContentProcessorInterface;
use App\Services\CampaignCommandService;
use App\Services\MailService;
use App\Services\MailServiceInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MailServiceInterface::class, MailService::class);

        $this->app->bind(EmailContentProcessorInterface::class, function ($app) {
            return EmailContentProcessorFactory::createBasicProcessor();
        });

        $this->app->singleton(CommandInvoker::class, function ($app) {
            return new CommandInvoker;
        });

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
