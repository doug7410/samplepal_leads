<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Repositories\CampaignRepositoryInterface;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\ContactRepositoryInterface;
use App\Repositories\EloquentCampaignRepository;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentContactRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Company repository
        $this->app->bind(CompanyRepositoryInterface::class, function () {
            return new EloquentCompanyRepository(new Company);
        });

        // Bind Contact repository
        $this->app->bind(ContactRepositoryInterface::class, function () {
            return new EloquentContactRepository(new Contact);
        });

        // Bind Campaign repository
        $this->app->bind(CampaignRepositoryInterface::class, function () {
            return new EloquentCampaignRepository(new Campaign);
        });
    }
}
