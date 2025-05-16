<?php

namespace App\Console\Commands;

use App\Jobs\CreateContactsForCompany;
use App\Models\Company;
use Illuminate\Console\Command;

class AgentCreateContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:agent-create-contacts {--limit=10 : Number of companies to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create contacts for companies using AI research (queued)';

    public function handle(): void
    {
        $limit = (int) $this->option('limit');
        $this->info("Dispatching jobs for up to {$limit} companies...");

        $companies = Company::with('contacts')
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->whereDoesntHave('contacts')
            ->take($limit)->get();

        $count = 0;
        
        foreach ($companies as $company) {
            $this->info("Queuing job for company: {$company->company_name} ({$company->website})");
            CreateContactsForCompany::dispatch($company);
            $count++;
        }

        $this->info("Dispatched {$count} jobs for contact creation.");
        $this->info("Jobs will be processed by queue workers. Run 'php artisan queue:work' if not already running.");
    }
}