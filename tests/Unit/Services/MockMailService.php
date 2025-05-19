<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\MailServiceInterface;
use Illuminate\Support\Facades\Log;

class MockMailService implements MailServiceInterface
{
    protected bool $throwException = false;

    protected bool $returnNull = false;

    protected string $messageId = 'test-message-id-123';

    protected string $exceptionMessage = 'Test mail sending error';

    public function shouldThrowException(string $message = 'Test mail sending error'): self
    {
        $this->throwException = true;
        $this->exceptionMessage = $message;

        return $this;
    }

    public function shouldReturnNull(): self
    {
        $this->returnNull = true;

        return $this;
    }

    public function withMessageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Send an email
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string
    {
        if ($this->throwException) {
            throw new \Exception($this->exceptionMessage);
        }

        if ($this->returnNull) {
            return null;
        }

        // Find or create campaign contact
        $campaignContact = CampaignContact::firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
            ],
            [
                'status' => 'pending',
            ]
        );

        // Only proceed if status is pending
        if ($campaignContact->status !== 'pending') {
            Log::info("Campaign contact #{$campaignContact->id} is not pending, skipping");

            return null;
        }

        // Update the campaign contact record
        $campaignContact->status = 'sent';
        $campaignContact->sent_at = now();
        $campaignContact->message_id = $this->messageId;
        $campaignContact->save();

        return $this->messageId;
    }

    /**
     * Send an email to all contacts in a company
     * 
     * @param Campaign $campaign Campaign containing email content and subject
     * @param \App\Models\Company $company Company whose contacts should receive the email
     * @param array $options Additional options for email sending
     * @return array Array of contact IDs mapped to message IDs or null values
     */
    public function sendEmailToCompany(Campaign $campaign, \App\Models\Company $company, array $options = []): array
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
        
        // Send email to each contact
        foreach ($contacts as $contact) {
            try {
                $messageId = $this->sendEmail($campaign, $contact, $options);
                $results[$contact->id] = $messageId;
            } catch (\Exception $e) {
                Log::error("Failed to send email to contact #{$contact->id} ({$contact->email}): " . $e->getMessage());
                $results[$contact->id] = null;
            }
        }
        
        return $results;
    }

    /**
     * Verify a tracking token
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        // Just return true for valid-token, false otherwise
        return $token === 'valid-token';
    }
}
