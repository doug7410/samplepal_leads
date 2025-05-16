<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\SesMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The campaign contact to process.
     */
    protected CampaignContact $campaignContact;

    /**
     * Create a new job instance.
     */
    public function __construct(CampaignContact $campaignContact)
    {
        $this->campaignContact = $campaignContact;
    }

    /**
     * Execute the job.
     */
    public function handle(SesMailService $mailService): void
    {
        // Get the campaign and contact
        $campaign = $this->campaignContact->campaign;
        $contact = $this->campaignContact->contact;
        
        // Skip if the campaign is not active
        if ($campaign->status !== 'in_progress') {
            Log::info("Campaign #{$campaign->id} is not in progress. Skipping email to {$contact->email}");
            return;
        }
        
        // Skip if already sent
        if ($this->campaignContact->status !== 'pending') {
            Log::info("Email to {$contact->email} already processed with status: {$this->campaignContact->status}");
            return;
        }
        
        // Send the email
        Log::info("Sending campaign email to {$contact->email}");
        $messageId = $mailService->sendEmail($campaign, $contact);
        
        if ($messageId) {
            Log::info("Email sent successfully to {$contact->email}, Message ID: {$messageId}");
        } else {
            Log::error("Failed to send email to {$contact->email}");
        }
    }
}
