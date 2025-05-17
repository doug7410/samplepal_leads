<?php

namespace App\Strategies\EmailTracking;

use App\Models\Campaign;
use App\Models\Contact;

interface TrackingStrategy
{
    /**
     * Add tracking to an email content
     *
     * @param  string  $content  The original email content
     * @param  Campaign  $campaign  The campaign being sent
     * @param  Contact  $contact  The contact receiving the email
     * @return string The content with tracking added
     */
    public function addTrackingToEmail(string $content, Campaign $campaign, Contact $contact): string;

    /**
     * Get a tracking pixel for email opens
     *
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string HTML for the tracking pixel
     */
    public function getTrackingPixel(int $campaignId, int $contactId): string;

    /**
     * Process links in the email content to add click tracking
     *
     * @param  string  $content  Email content with links
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string The content with tracking links
     */
    public function processLinksForTracking(string $content, int $campaignId, int $contactId): string;

    /**
     * Generate a tracking token for security
     *
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string Secure token
     */
    public function generateTrackingToken(int $campaignId, int $contactId): string;

    /**
     * Verify a tracking token
     *
     * @param  string  $token  Token to verify
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return bool True if valid
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool;
}
