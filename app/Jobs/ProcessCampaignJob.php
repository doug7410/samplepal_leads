<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The campaign to process.
     */
    protected Campaign $campaign;
    
    /**
     * The number of emails per batch.
     */
    protected int $batchSize = 50;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if the campaign should be processed
        if ($this->campaign->status !== 'in_progress') {
            Log::info("Campaign #{$this->campaign->id} is not in progress. Skipping.");
            return;
        }
        
        // Get all pending campaign contacts
        $pendingContactsCount = $this->campaign->campaignContacts()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingContactsCount === 0) {
            // All emails have been sent, mark campaign as completed
            Log::info("Campaign #{$this->campaign->id} has no pending emails. Marking as completed.");
            $this->campaign->status = 'completed';
            $this->campaign->completed_at = now();
            $this->campaign->save();
            return;
        }
        
        // Process a batch of pending contacts
        $pendingContacts = $this->campaign->campaignContacts()
            ->where('status', 'pending')
            ->take($this->batchSize)
            ->get();
            
        Log::info("Processing {$pendingContacts->count()} emails for campaign #{$this->campaign->id}");
        
        // Dispatch individual email sending jobs
        foreach ($pendingContacts as $campaignContact) {
            SendCampaignEmailJob::dispatch($campaignContact);
        }
        
        // If there are more pending contacts, dispatch another batch job
        if ($pendingContactsCount > $this->batchSize) {
            // Schedule the next batch with a delay to prevent throttling
            ProcessCampaignJob::dispatch($this->campaign)
                ->delay(now()->addMinutes(1));
        }
    }
}
