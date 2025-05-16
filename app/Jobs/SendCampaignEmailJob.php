<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\MailService;
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
    public function handle(MailService $mailService): void
    {
        // Get the campaign and contact
        $campaign = $this->campaignContact->campaign;
        $contact = $this->campaignContact->contact;
        
        // Skip if the campaign is not active
        if ($campaign->status !== 'in_progress') {
            Log::info("Campaign #{$campaign->id} is not in progress. Skipping email to {$contact->email}");
            return;
        }
        
        // Skip if already processed
        if ($this->campaignContact->status !== 'pending') {
            Log::info("Email to {$contact->email} already processed with status: {$this->campaignContact->status}");
            return;
        }
        
        try {
            // Send the email
            Log::info("Sending campaign email to {$contact->email}");
            $messageId = $mailService->sendEmail($campaign, $contact);
            
            if ($messageId) {
                Log::info("Email sent successfully to {$contact->email}, Message ID: {$messageId}");
            } else {
                // If sendEmail returns null but doesn't throw an exception, still mark as failed
                Log::error("Failed to send email to {$contact->email} (no message ID returned)");
                $this->markAsFailed("No message ID returned from mail service");
            }
        } catch (\Exception $e) {
            // Catch any exceptions that weren't caught by the mail service
            Log::error("Exception sending email to {$contact->email}: " . $e->getMessage());
            $this->markAsFailed($e->getMessage());
            
            // We don't rethrow the exception so the job is considered "processed"
            // and won't be retried, unless we configure retries explicitly
        }
    }
    
    /**
     * Mark the campaign contact as failed.
     */
    protected function markAsFailed(string $reason): void
    {
        try {
            // Double check that the campaign contact still exists and is in pending state
            $campaignContact = CampaignContact::where('id', $this->campaignContact->id)
                ->where('status', 'pending')
                ->first();
                
            if ($campaignContact) {
                $campaignContact->status = 'failed';
                $campaignContact->failed_at = now();
                $campaignContact->failure_reason = $reason;
                $campaignContact->save();
                
                Log::info("Marked campaign contact #{$campaignContact->id} as failed: {$reason}");
            } else {
                Log::warning("Campaign contact #{$this->campaignContact->id} not found or not in pending state, could not mark as failed");
            }
        } catch (\Exception $e) {
            Log::error("Error marking campaign contact as failed: " . $e->getMessage());
        }
    }
}
