<?php

namespace App\Strategies\EmailTracking;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Support\Str;

class DefaultTrackingStrategy implements TrackingStrategy
{
    /**
     * Add tracking to an email content
     *
     * @param  string  $content  The original email content
     * @param  Campaign  $campaign  The campaign being sent
     * @param  Contact  $contact  The contact receiving the email
     * @return string The content with tracking added
     */
    public function addTrackingToEmail(string $content, Campaign $campaign, Contact $contact): string
    {
        // Process the content to add tracking pixels and link tracking
        $content = $this->processLinksForTracking($content, $campaign->id, $contact->id);
        $content .= $this->getTrackingPixel($campaign->id, $contact->id);

        return $content;
    }

    /**
     * Get a tracking pixel for email opens
     *
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string HTML for the tracking pixel
     */
    public function getTrackingPixel(int $campaignId, int $contactId): string
    {
        $trackingUrl = route('email.track.open', [
            'campaign' => $campaignId,
            'contact' => $contactId,
            'token' => $this->generateTrackingToken($campaignId, $contactId),
        ]);

        return '<img src="'.$trackingUrl.'" alt="" width="1" height="1" style="display:none;" />';
    }

    /**
     * Process links in the email content to add click tracking
     *
     * @param  string  $content  Email content with links
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string The content with tracking links
     */
    public function processLinksForTracking(string $content, int $campaignId, int $contactId): string
    {
        // Use regular expressions to find and replace all links in the HTML
        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/i';

        return preg_replace_callback($pattern, function ($matches) use ($campaignId, $contactId) {
            $originalUrl = $matches[2];

            // Don't track mailto: links
            if (Str::startsWith($originalUrl, 'mailto:')) {
                return $matches[0];
            }

            // Generate a tracking URL
            $trackingUrl = route('email.track.click', [
                'campaign' => $campaignId,
                'contact' => $contactId,
                'token' => $this->generateTrackingToken($campaignId, $contactId),
                'url' => base64_encode($originalUrl),
            ]);

            // Replace the original URL with the tracking URL
            return str_replace($originalUrl, $trackingUrl, $matches[0]);
        }, $content);
    }

    /**
     * Generate a tracking token for security
     *
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return string Secure token
     */
    public function generateTrackingToken(int $campaignId, int $contactId): string
    {
        $key = config('app.key');

        return hash_hmac('sha256', "campaign:{$campaignId},contact:{$contactId}", $key);
    }

    /**
     * Verify a tracking token
     *
     * @param  string  $token  Token to verify
     * @param  int  $campaignId  Campaign ID
     * @param  int  $contactId  Contact ID
     * @return bool True if valid
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        $expectedToken = $this->generateTrackingToken($campaignId, $contactId);

        return hash_equals($expectedToken, $token);
    }
}
