<?php

namespace App\Services;

use App\Decorators\EmailContent\EmailContentProcessorFactory;
use App\Decorators\EmailContent\EmailContentProcessorInterface;
use App\Helpers\RecipientsFormatter;
use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

abstract class AbstractMailService implements MailServiceInterface
{
    /**
     * Email content processor
     */
    protected EmailContentProcessorInterface $contentProcessor;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentProcessor = EmailContentProcessorFactory::createBasicProcessor();
    }

    /**
     * Send an email using the template method pattern
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Contact  $contact  Contact to send email to
     * @param  array  $options  Additional options for email sending
     * @return string|null Message ID on success, null on failure
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string
    {
        try {
            // 1. Prepare campaign contact (validate and mark as processing)
            $campaignContact = $this->prepareCampaignContact($campaign, $contact);
            if (! $campaignContact) {
                return null;
            }

            // 2. Process subject and email content
            $subject = $this->parseTemplate($campaign->subject, $contact);
            $htmlBody = $this->contentProcessor->process($campaign->content, $campaign, $contact);

            // 3. Perform the actual email delivery (implemented by concrete classes)
            $messageId = $this->deliverEmail($campaign, $contact, $subject, $htmlBody, $options);

            // 4. Update the campaign contact status based on delivery result
            if ($messageId) {
                $this->markCampaignContactSent($campaignContact, $messageId);
            } else {
                $this->markCampaignContactFailed($campaignContact, 'Email delivery failed - no message ID returned');
            }

            return $messageId;
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$contact->email}: ".$e->getMessage());

            // Try to mark the campaign contact as failed
            try {
                $this->markCampaignContactFailed(
                    $campaignContact ?? null,
                    $e->getMessage()
                );
            } catch (\Exception $ex) {
                Log::error('Error marking campaign contact as failed: '.$ex->getMessage());
            }

            return null;
        }
    }

    /**
     * Email all contacts in a company
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Company  $company  Company whose contacts should receive the email
     * @param  array  $options  Additional options for email sending
     * @return array Array of contact IDs mapped to message IDs or null values
     */
    public function sendEmailToCompany(Campaign $campaign, Company $company, array $options = []): array
    {
        $results = [];

        // Get all contacts for the company
        $contacts = $company->contacts()
            ->whereNotNull('email')
            ->get();

        if ($contacts->isEmpty()) {
            Log::warning("No valid contacts found for company #{$company->id} ({$company->name})");

            return $results;
        }

        Log::info("Sending campaign #{$campaign->id} to {$contacts->count()} contacts in company #{$company->id} ({$company->name})");

        // Find all existing campaign contacts or create them if they don't exist
        foreach ($contacts as $contact) {
            try {
                // Get the campaign contact record
                $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                    ->where('contact_id', $contact->id)
                    ->first();

                if (! $campaignContact) { // TODO: the mail service should not have to create contacts.
                    Log::info("Creating campaign contact record for campaign #{$campaign->id} and contact #{$contact->id}");
                    $campaignContact = new CampaignContact([
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'status' => CampaignContact::STATUS_PENDING,
                    ]);
                    $campaignContact->save();
                }

                // Only dispatch job if the status is pending
                if ($campaignContact->status === CampaignContact::STATUS_PENDING) {
                    SendCampaignEmailJob::dispatch($campaignContact);
                    Log::info("Queued email for contact #{$contact->id}");
                    $results[$contact->id] = 'queued';
                } else {
                    // If already processed, add to results with appropriate status
                    $results[$contact->id] = $campaignContact->status;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue email for contact #{$contact->id} ({$contact->email}): ".$e->getMessage());
                $results[$contact->id] = null;
            }
        }

        return $results;
    }

    /**
     * Verify a tracking token. (No longer used)
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        // No longer needed, but maintained for interface compatibility
        return false;
    }

    /**
     * Prepare campaign contact for email sending
     *
     * @return CampaignContact|null The prepared campaign contact or null on failure
     */
    protected function prepareCampaignContact(Campaign $campaign, Contact $contact): ?CampaignContact
    {
        try {
            // Make sure we have the proper contact id, since we're receiving objects that may be stale
            if (! $contact->id) {
                Log::error('Contact has no ID: '.json_encode($contact->toArray()));
                throw new \Exception('Contact has no ID');
            }

            // Get the campaign contact record
            $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->first();

            if (! $campaignContact) {
                Log::error("Campaign contact record not found for campaign #{$campaign->id} and contact #{$contact->id}");

                // Log more details to help diagnose the issue
                $existingCount = CampaignContact::where('campaign_id', $campaign->id)->count();
                Log::error("Campaign has {$existingCount} contacts total");

                throw new \Exception('Campaign contact record not found');
            }

            // Check if campaign contact is already being processed or has been processed
            if ($campaignContact->status !== CampaignContact::STATUS_PENDING) {
                Log::warning("Campaign contact #{$campaignContact->id} has status '{$campaignContact->status}', not processing again");

                return null;
            }

            // Mark as processing to prevent duplicate processing
            $campaignContact->status = CampaignContact::STATUS_PROCESSING;
            $campaignContact->save();

            // Ensure the status was saved correctly
            if ($campaignContact->fresh()->status !== CampaignContact::STATUS_PROCESSING) {
                Log::error("Failed to update campaign contact #{$campaignContact->id} status to 'processing'. Current status: ".$campaignContact->fresh()->status);
                throw new \Exception('Failed to update campaign contact status');
            }

            return $campaignContact;
        } catch (\Exception $e) {
            Log::error('Failed to prepare campaign contact: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Parse template variables in content.
     */
    protected function parseTemplate(string $content, Contact $contact): string
    {
        $replacements = [
            '{{first_name}}' => $contact->first_name,
            '{{last_name}}' => $contact->last_name,
            '{{full_name}}' => trim($contact->first_name.' '.$contact->last_name),
            '{{email}}' => $contact->email,
            '{{company}}' => optional($contact->company)->name ?? '',
            '{{job_title}}' => $contact->job_title ?? '',
        ];

        // Handle {{recipients}} variable for company campaigns
        if (strpos($content, '{{recipients}}') !== false && $contact->company_id) {
            // Get all contacts for the company
            $companyContacts = $contact->company->contacts()
                ->whereNotNull('email')
                ->where('id', '!=', $contact->id) // Exclude current contact
                ->get();

            // Add current contact to the beginning
            $companyContacts->prepend($contact);

            // Format the recipients list
            $replacements['{{recipients}}'] = RecipientsFormatter::format($companyContacts);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Set a custom email content processor
     */
    public function setContentProcessor(EmailContentProcessorInterface $processor): self
    {
        $this->contentProcessor = $processor;

        return $this;
    }

    /**
     * Mark a campaign contact as sent
     */
    protected function markCampaignContactSent(CampaignContact $campaignContact, string $messageId): void
    {
        try {
            // Make sure we're updating the right record
            $campaignContact = CampaignContact::find($campaignContact->id);

            if ($campaignContact) {
                $campaignContact->message_id = $messageId;
                $campaignContact->status = CampaignContact::STATUS_SENT;
                $campaignContact->sent_at = now();
                $campaignContact->save();

                Log::info("Updated campaign contact #{$campaignContact->id} status to sent");
            } else {
                Log::warning("Campaign contact #{$campaignContact->id} not found, could not update status to sent");
            }
        } catch (\Exception $e) {
            Log::error('Error updating campaign contact status: '.$e->getMessage());
            throw $e; // Re-throw to be handled by caller
        }
    }

    /**
     * Mark a campaign contact as failed
     */
    protected function markCampaignContactFailed(?CampaignContact $campaignContact, string $reason): void
    {
        if (! $campaignContact) {
            return;
        }

        try {
            // Double check that the campaign contact still exists and is in pending or processing state
            $contactToUpdate = CampaignContact::where('id', $campaignContact->id)
                ->whereIn('status', [CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PROCESSING])
                ->first();

            if ($contactToUpdate) {
                $contactToUpdate->status = CampaignContact::STATUS_FAILED;
                $contactToUpdate->failed_at = now();
                $contactToUpdate->failure_reason = $reason;
                $contactToUpdate->save();

                Log::info("Marked campaign contact #{$contactToUpdate->id} as failed: {$reason}");
            } else {
                // Check the current status
                $current = CampaignContact::find($campaignContact->id);
                $currentStatus = $current ? $current->status : 'unknown';
                Log::warning("Campaign contact #{$campaignContact->id} has status '{$currentStatus}', could not mark as failed");
            }
        } catch (\Exception $e) {
            Log::error('Error marking campaign contact as failed: '.$e->getMessage());
        }
    }

    /**
     * Deliver the email (to be implemented by concrete mail service classes)
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Contact  $contact  Contact to send email to
     * @param  string  $subject  Processed email subject
     * @param  string  $htmlBody  Processed HTML body with tracking elements
     * @param  array  $options  Additional options for email sending
     * @return string|null Message ID on success, null on failure
     */
    abstract protected function deliverEmail(
        Campaign $campaign,
        Contact $contact,
        string $subject,
        string $htmlBody,
        array $options
    ): ?string;
}
