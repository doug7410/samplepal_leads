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
     * Verify a tracking token
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        // Just return true for valid-token, false otherwise
        return $token === 'valid-token';
    }
}