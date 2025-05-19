<?php

namespace Tests\Unit\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Services\MailServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * A concrete implementation of MailServiceInterface for testing
 */
class MockMailService implements MailServiceInterface
{
    /**
     * Whether to throw an exception on sendEmail
     */
    protected bool $throwException = false;

    /**
     * Whether to return null on sendEmail
     */
    protected bool $returnNull = false;

    /**
     * The message ID to return on success
     */
    protected string $messageId = 'test-message-id-123';

    /**
     * Exception message to throw
     */
    protected string $exceptionMessage = 'Test mail sending error';

    /**
     * Configure the mock to throw an exception
     */
    public function shouldThrowException(string $message = 'Test mail sending error'): self
    {
        $this->throwException = true;
        $this->exceptionMessage = $message;

        return $this;
    }

    /**
     * Configure the mock to return null
     */
    public function shouldReturnNull(): self
    {
        $this->returnNull = true;

        return $this;
    }

    /**
     * Configure the message ID to return
     */
    public function withMessageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Send an email
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Contact  $contact  Contact to send email to
     * @param  array  $options  Additional options for email sending
     * @return string|null Message ID on success, null on failure
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string
    {
        Log::info('MockMailService.sendEmail called');

        if ($this->throwException) {
            throw new \Exception($this->exceptionMessage);
        }

        if ($this->returnNull) {
            return null;
        }

        return $this->messageId;
    }

    /**
     * Send an email to all contacts in a company
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  \App\Models\Company  $company  Company whose contacts should receive the email
     * @param  array  $options  Additional options for email sending
     * @return array Array of contact IDs mapped to message IDs or null values
     */
    public function sendEmailToCompany(Campaign $campaign, \App\Models\Company $company, array $options = []): array
    {
        Log::info('MockMailService.sendEmailToCompany called');

        $results = [];

        // Get all contacts for the company
        $contacts = $company->contacts()
            ->whereNotNull('email')
            ->get();

        if ($contacts->isEmpty()) {
            Log::warning("No valid contacts found for company #{$company->id} ({$company->name})");

            return $results;
        }

        // Send email to each contact
        foreach ($contacts as $contact) {
            try {
                $messageId = $this->sendEmail($campaign, $contact, $options);
                $results[$contact->id] = $messageId;
            } catch (\Exception $e) {
                Log::error("Failed to send email to contact #{$contact->id} ({$contact->email}): ".$e->getMessage());
                $results[$contact->id] = null;
            }
        }

        return $results;
    }

    /**
     * Verify a tracking token (stub implementation)
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        return true;
    }
}
