<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;

interface MailServiceInterface
{
    /**
     * Send an email
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Contact  $contact  Contact to send email to
     * @param  array  $options  Additional options for email sending
     * @return string|null Message ID on success, null on failure
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string;

    /**
     * Send an email to all contacts in a company
     * 
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  \App\Models\Company  $company  Company whose contacts should receive the email
     * @param  array  $options  Additional options for email sending
     * @return array Array of contact IDs mapped to message IDs or null values
     */
    public function sendEmailToCompany(Campaign $campaign, \App\Models\Company $company, array $options = []): array;

    /**
     * Verify a tracking token
     *
     * @param  string  $token  Token to verify
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return bool True if token is valid
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool;
}
