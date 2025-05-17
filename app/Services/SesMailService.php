<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SesMailService
{
    protected SesClient $sesClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region' => config('services.ses.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);
    }

    /**
     * Send an email using SES.
     *
     * @return string|null Message ID on success, null on failure
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string
    {
        // Get the campaign contact record
        $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $contact->id)
            ->first();

        if (! $campaignContact) {
            Log::error("Campaign contact record not found for campaign #{$campaign->id} and contact #{$contact->id}");

            return null;
        }

        try {
            // Parse the content and inject tracking pixels/links
            $subject = $this->parseTemplate($campaign->subject, $contact);
            $htmlBody = $this->parseTemplate($campaign->content, $contact);

            // Add tracking pixel
            $trackingPixel = $this->getTrackingPixel($campaign->id, $contact->id);
            $htmlBody .= $trackingPixel;

            // Process links for click tracking
            $htmlBody = $this->processLinksForTracking($htmlBody, $campaign->id, $contact->id);

            // Prepare the email
            $sender = $campaign->from_name
                ? "{$campaign->from_name} <{$campaign->from_email}>"
                : $campaign->from_email;

            $replyTo = $campaign->reply_to ?? $campaign->from_email;

            $params = [
                'Source' => $sender,
                'Destination' => [
                    'ToAddresses' => [$contact->email],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Html' => [
                            'Data' => $htmlBody,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
                'ReplyToAddresses' => [$replyTo],
                'ConfigurationSetName' => config('services.ses.configuration_set', 'default'),
            ];

            // Add custom tags for tracking
            $params['Tags'] = [
                [
                    'Name' => 'campaign_id',
                    'Value' => (string) $campaign->id,
                ],
                [
                    'Name' => 'contact_id',
                    'Value' => (string) $contact->id,
                ],
            ];

            // Send the email
            $result = $this->sesClient->sendEmail($params);

            // Save the message ID for tracking
            $messageId = $result->get('MessageId');
            $campaignContact->message_id = $messageId;
            $campaignContact->status = 'sent';
            $campaignContact->sent_at = now();
            $campaignContact->save();

            return $messageId;
        } catch (\Exception $e) {
            // Log the error and update the campaign contact
            Log::error("Failed to send email to {$contact->email}: ".$e->getMessage());

            $campaignContact->status = 'failed';
            $campaignContact->failed_at = now();
            $campaignContact->failure_reason = $e->getMessage();
            $campaignContact->save();

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

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Generate a tracking pixel for email opens.
     */
    protected function getTrackingPixel(int $campaignId, int $contactId): string
    {
        $trackingUrl = route('email.track.open', [
            'campaign' => $campaignId,
            'contact' => $contactId,
            'token' => $this->generateTrackingToken($campaignId, $contactId),
        ]);

        return '<img src="'.$trackingUrl.'" alt="" width="1" height="1" style="display:none;" />';
    }

    /**
     * Process links in the email content to add click tracking.
     */
    protected function processLinksForTracking(string $content, int $campaignId, int $contactId): string
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
     * Generate a tracking token for security.
     */
    protected function generateTrackingToken(int $campaignId, int $contactId): string
    {
        $key = config('app.key');

        return hash_hmac('sha256', "campaign:{$campaignId},contact:{$contactId}", $key);
    }

    /**
     * Verify a tracking token.
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        $expectedToken = $this->generateTrackingToken($campaignId, $contactId);

        return hash_equals($expectedToken, $token);
    }
}
