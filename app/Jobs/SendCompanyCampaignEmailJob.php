<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Company;
use App\Services\MailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCompanyCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The campaign to send
     */
    protected Campaign $campaign;

    /**
     * The company to send to
     */
    protected Company $company;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, Company $company)
    {
        $this->campaign = $campaign;
        $this->company = $company;
    }

    /**
     * Execute the job.
     */
    public function handle(MailServiceInterface $mailService): void
    {
        // Skip if the campaign is not active
        if ($this->campaign->status !== Campaign::STATUS_IN_PROGRESS) {
            Log::info("Campaign #{$this->campaign->id} is not in progress. Skipping emails to company #{$this->company->id}");

            return;
        }

        try {
            // Send emails to all company contacts
            Log::info("Sending campaign #{$this->campaign->id} to company #{$this->company->id} ({$this->company->name})");

            $results = $mailService->sendEmailToCompany($this->campaign, $this->company);

            // Log results
            $successCount = count(array_filter($results));
            $failureCount = count($results) - $successCount;

            Log::info("Campaign #{$this->campaign->id} to company #{$this->company->id}: {$successCount} sent, {$failureCount} failed");

            if ($failureCount > 0 && $successCount === 0) {
                // All emails to this company failed
                Log::error("All emails to company #{$this->company->id} failed for campaign #{$this->campaign->id}");
            } elseif ($failureCount > 0) {
                // Some emails failed
                Log::warning("Some emails to company #{$this->company->id} failed for campaign #{$this->campaign->id}");
            } else {
                // All emails succeeded
                Log::info("All emails to company #{$this->company->id} sent successfully for campaign #{$this->campaign->id}");
            }
        } catch (\Exception $e) {
            // Log the error but don't rethrow
            Log::error("Error sending campaign #{$this->campaign->id} to company #{$this->company->id}: ".$e->getMessage());
        }
    }
}
