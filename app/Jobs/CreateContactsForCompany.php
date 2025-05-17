<?php

namespace App\Jobs;

use App\Agents\CustomerResearchAgent;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateContactsForCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes (300 seconds)

    /**
     * The company for which to create contacts.
     */
    protected Company $company;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing company: {$this->company->company_name} ({$this->company->website})");

        try {
            $researchAgent = new CustomerResearchAgent;
            $researchResults = $researchAgent->researchByCompany($this->company);

            if (isset($researchResults['contacts']) && ! empty($researchResults['contacts'])) {
                Log::info('Found '.count($researchResults['contacts']).' potential contacts for '.$this->company->company_name);

                // Store each contact in the database
                foreach ($researchResults['contacts'] as $contactData) {
                    // Create the contact
                    $newContact = Contact::create($contactData);
                    Log::info("Created contact: {$newContact->first_name} {$newContact->last_name} for {$this->company->company_name}");
                }

                Log::info("Successfully processed all contacts for {$this->company->company_name}.");
            } else {
                Log::warning("No valid contacts data found in the research results for {$this->company->company_name}.");
            }
        } catch (\Exception $e) {
            Log::error("Error processing company {$this->company->company_name}: ".$e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}
