<?php

namespace App\Console\Commands;

use App\Agents\CustomerResearchAgent;
use App\Models\Company;
use App\Models\Contact;
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
    protected $description = 'Create contacts for companies using AI research';

    public function handle(): void
    {
        $limit = (int) $this->option('limit');
        $this->info("Processing up to {$limit} companies...");

        $companies = Company::with('contacts')
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->whereDoesntHave('contacts')
            ->take($limit)->get();

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->company_name} ({$company->website})");

            try {
                $researchAgent = new CustomerResearchAgent;
                $researchResults = $researchAgent->researchByCompany($company);

                if (isset($researchResults['contacts']) && ! empty($researchResults['contacts'])) {
                    $this->info('Found '.count($researchResults['contacts']).' potential contacts.');

                    // Store each contact in the database
                    foreach ($researchResults['contacts'] as $contactData) {
                        // Create the contact
                        $newContact = Contact::create($contactData);
                        $this->info("Created contact: {$newContact->first_name} {$newContact->last_name}");
                    }

                    $this->newLine();
                    $this->info("Successfully processed all contacts for {$company->company_name}.");
                } else {
                    $this->error('No valid contacts data found in the research results.');
                }
            } catch (\Exception $e) {
                $this->error('Error during research process: '.$e->getMessage());
            }
        }

    }
}
