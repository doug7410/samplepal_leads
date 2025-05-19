<?php

namespace App\Jobs;

use App\Jobs\Middleware\RateLimitEmailJobs;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\MailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new RateLimitEmailJobs];
    }

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
    public function handle(MailServiceInterface $mailService): void
    {
        // Get the campaign and contact
        $campaign = $this->campaignContact->campaign;
        $contact = $this->campaignContact->contact;

        // Skip if the campaign is not active
        if ($campaign->status !== Campaign::STATUS_IN_PROGRESS) {
            Log::info("Campaign #{$campaign->id} is not in progress. Skipping email to {$contact->email}");

            return;
        }

        // Skip if already processed
        if ($this->campaignContact->status !== CampaignContact::STATUS_PENDING) {
            Log::info("Email to {$contact->email} already processed with status: {$this->campaignContact->status}");

            return;
        }

        try {
            // Send the email
            Log::info("Sending campaign email to {$contact->email}");

            try {
                $messageId = $mailService->sendEmail($campaign, $contact);

                if ($messageId) {
                    Log::info("Email sent successfully to {$contact->email}, Message ID: {$messageId}");
                } else {
                    // Check if the status was already updated directly in the MailService
                    $freshCampaignContact = CampaignContact::find($this->campaignContact->id);

                    if ($freshCampaignContact && $freshCampaignContact->status === CampaignContact::STATUS_SENT) {
                        Log::info("Email to {$contact->email} was sent but no message ID was returned");
                    } else {
                        // If sendEmail returns null but doesn't throw an exception, still mark as failed
                        Log::error("Failed to send email to {$contact->email} (no message ID returned)");
                        $this->markAsFailed('No message ID returned from mail service');
                    }
                }
            } catch (\Exception $e) {
                // Double-check the status - the email might have been sent before the exception
                $freshCampaignContact = CampaignContact::find($this->campaignContact->id);

                if ($freshCampaignContact && $freshCampaignContact->status === CampaignContact::STATUS_SENT) {
                    Log::info("Email to {$contact->email} was sent despite exception: {$e->getMessage()}");
                } else {
                    // If there was an exception and the status was not updated, mark as failed
                    Log::error("Exception sending email to {$contact->email}: ".$e->getMessage());
                    $this->markAsFailed($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Catch any exceptions that weren't caught by the mail service
            Log::error("Exception sending email to {$contact->email}: ".$e->getMessage());
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
            // First check if the campaign contact has already been marked as sent
            // This could happen if the email was sent but there was an error after
            $freshCampaignContact = CampaignContact::find($this->campaignContact->id);

            if ($freshCampaignContact && $freshCampaignContact->status === CampaignContact::STATUS_SENT) {
                Log::info("Not marking campaign contact #{$freshCampaignContact->id} as failed because it's already marked as sent");

                return;
            }

            // Double check that the campaign contact still exists and is in pending or processing state
            $campaignContact = CampaignContact::where('id', $this->campaignContact->id)
                ->whereIn('status', [CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PROCESSING])
                ->first();

            if ($campaignContact) {
                $campaignContact->status = CampaignContact::STATUS_FAILED;
                $campaignContact->failed_at = now();
                $campaignContact->failure_reason = $reason;
                $campaignContact->save();

                Log::info("Marked campaign contact #{$campaignContact->id} as failed: {$reason}");
            } else {
                // Check the current status
                $currentStatus = $freshCampaignContact ? $freshCampaignContact->status : 'unknown';
                Log::warning("Campaign contact #{$this->campaignContact->id} has status '{$currentStatus}', could not mark as failed");
            }
        } catch (\Exception $e) {
            Log::error('Error marking campaign contact as failed: '.$e->getMessage());
        }
    }
}
