<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class AgentCreateContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:agent-create-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query all companies and display them';

    /**
     * Fix website URLs that are actually email addresses
     */
    private function fixWebsiteUrl(string $website): string
    {
        // Check if the website is actually an email address
        if (str_starts_with($website, 'mailto:')) {
            // Extract domain from email
            preg_match('/mailto:.*@(.+)/', $website, $matches);
            if (isset($matches[1])) {
                return 'https://' . $matches[1];
            }
        }

        // Fix URLs without http/https protocol
        if (!str_starts_with($website, 'http://') && !str_starts_with($website, 'https://')) {
            return 'https://' . $website;
        }

        return $website;
    }

    public function handle()
    {
        $companies = Company::with('contacts')
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->get()
            ->filter(function ($company) {
                return $company->contacts->count() === 0;
            });
        

        foreach ($companies as $company) {
            $fixedWebsite = $this->fixWebsiteUrl($company->website);
            $this->line("Website: {$fixedWebsite}");
        }
    }
}
