<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Services\MailServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class EmailTrackingController extends Controller
{
    protected MailServiceInterface $mailService;

    /**
     * Constructor
     */
    public function __construct(MailServiceInterface $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Track email opens.
     */
    public function trackOpen(Request $request, Campaign $campaign, Contact $contact): Response
    {
        $token = $request->query('token');

        // Validate the token
        if (! $this->mailService->verifyTrackingToken($token, $campaign->id, $contact->id)) {
            return response('Invalid token', 403);
        }

        // Record the open event
        $this->recordEvent($campaign, $contact, 'opened', $request);

        // Return a transparent 1x1 pixel
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Track email link clicks.
     */
    public function trackClick(Request $request, Campaign $campaign, Contact $contact)
    {
        $token = $request->query('token');
        $encodedUrl = $request->query('url');

        // Validate the token
        if (! $this->mailService->verifyTrackingToken($token, $campaign->id, $contact->id)) {
            return response('Invalid token', 403);
        }

        try {
            // Decode the original URL
            $url = base64_decode($encodedUrl);

            // Record the click event
            $this->recordEvent($campaign, $contact, 'clicked', $request, [
                'url' => $url,
            ]);

            // Redirect to the original URL
            return redirect($url);
        } catch (\Exception $e) {
            Log::error('Error tracking click: '.$e->getMessage());

            return response('Error processing link', 500);
        }
    }

    /**
     * Handle Resend webhook notification.
     */
    public function handleWebhook(Request $request): Response
    {
        // Get the raw request content to prevent JSON encoding/decoding inconsistencies
        $payload = json_decode($request->getContent(), true);

        Log::info('Webhook received', ['payload' => $payload]);

        // Verify the webhook signature if a secret is configured
        // For production, we should verify signatures
        // For testing/development, you can disable this check if needed
        $webhookSecret = config('services.resend.webhook_secret');
        if ($webhookSecret) {
            $signature = $request->header('Svix-Signature');

            if (config('app.env') === 'production') {
                // In production, we strictly verify signatures
                if (! $signature || ! $this->verifyResendSignature($payload, $signature, $webhookSecret)) {
                    Log::warning('Invalid webhook signature in production', [
                        'signature' => $signature,
                        'svix_id' => $request->header('Svix-Id'),
                        'timestamp' => $request->header('Svix-Timestamp'),
                    ]);

                    return response('Invalid signature', 403);
                }
            } else {
                // In development, log but don't block on invalid signatures
                $isValid = $this->verifyResendSignature($payload, $signature, $webhookSecret);
                Log::info('Signature verification result: '.($isValid ? 'valid' : 'invalid'));
            }
        }

        // Verify it's a valid Resend event
        if (! isset($payload['type'])) {
            Log::warning('Invalid webhook payload', ['payload' => $payload]);

            return response('Invalid payload', 400);
        }

        // Map Resend event types to our internal event types
        $eventTypeMap = [
            'email.delivered' => 'delivered',
            'email.delivery_delayed' => 'delayed',
            'email.complained' => 'complaint',
            'email.bounced' => 'bounce',
            'email.opened' => 'opened',
            'email.clicked' => 'clicked',
        ];

        $event = $eventTypeMap[$payload['type']] ?? $payload['type'];

        // Extract data from the payload
        $data = $payload['data'] ?? [];

        // Try to extract campaign and contact IDs from headers
        $campaignId = null;
        $contactId = null;
        $headers = $data['headers'] ?? [];

        // Headers in Resend payload come as an array of objects with 'name' and 'value' properties
        foreach ($headers as $header) {
            if (isset($header['name']) && isset($header['value'])) {
                if ($header['name'] === 'X-Campaign-ID') {
                    $campaignId = (int) $header['value'];
                } elseif ($header['name'] === 'X-Contact-ID') {
                    $contactId = (int) $header['value'];
                }
            }
        }

        // Log the extracted IDs for debugging
        Log::debug('Extracted campaign and contact IDs', [
            'campaignId' => $campaignId,
            'contactId' => $contactId,
            'headers' => $headers,
        ]);

        // If we have campaign and contact IDs, record the event
        if ($campaignId && $contactId) {
            $campaign = Campaign::find($campaignId);
            $contact = Contact::find($contactId);

            if ($campaign && $contact) {
                $eventData = [
                    'message_id' => $data['email_id'] ?? $data['id'] ?? null,
                    'resend_data' => $data,
                ];

                $this->recordEvent($campaign, $contact, $event, null, $eventData);
                Log::info('Recorded email event', [
                    'event' => $event,
                    'campaign' => $campaignId,
                    'contact' => $contactId,
                ]);
            } else {
                Log::warning('Campaign or contact not found', [
                    'campaignId' => $campaignId,
                    'contactId' => $contactId,
                ]);
            }
        } else {
            Log::warning('Missing campaign or contact ID in webhook payload');
        }

        return response('OK', 200);
    }

    /**
     * Verify Resend webhook signature using Svix specification
     *
     * @see https://docs.svix.com/receiving/verifying-payloads/how-to-verify
     */
    protected function verifyResendSignature(array $payload, ?string $signatureHeader = null, string $secret = ''): bool
    {
        try {
            // Make sure we have all required inputs
            if (! $signatureHeader) {
                Log::error('Missing signature header');

                return false;
            }

            // Get the raw body content - we need the exact string that was signed
            $bodyContent = request()->getContent();

            // Get message ID and timestamp
            $messageId = request()->header('Svix-Id');
            $timestamp = request()->header('Svix-Timestamp');

            if (! $messageId || ! $timestamp || ! $signatureHeader) {
                Log::error('Missing required Svix headers');

                return false;
            }

            // Check for stale timestamps (5 minute tolerance)
            $tolerance = 5 * 60; // 5 minutes in seconds
            if (abs(time() - intval($timestamp)) > $tolerance) {
                Log::warning('Webhook timestamp is too old');

                return false;
            }

            // Parse signature header (format: "v1,signature")
            if (! preg_match('/^v1,(.+)$/', $signatureHeader, $matches)) {
                Log::error('Invalid signature format');

                return false;
            }

            $signature = $matches[1];

            // Construct the signed payload string exactly as Svix does
            $toSign = $messageId.'.'.$timestamp.'.'.$bodyContent;

            // Calculate our signature using HMAC SHA-256 algorithm
            $expectedSignature = base64_encode(
                hash_hmac('sha256', $toSign, $secret, true)
            );

            // Verify using constant-time comparison to prevent timing attacks
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Error verifying Resend signature: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Record an email event.
     */
    protected function recordEvent(Campaign $campaign, Contact $contact, string $eventType, ?Request $request, array $eventData = []): void
    {
        try {
            // Create the event record
            $event = new EmailEvent([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'event_type' => $eventType,
                'event_time' => now(),
                'event_data' => $eventData,
            ]);

            // Add request information if available
            if ($request) {
                $event->ip_address = $request->ip();
                $event->user_agent = $request->userAgent();
            }

            $event->save();

            // Update campaign contact status
            $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->first();

            if ($campaignContact) {
                switch ($eventType) {
                    case 'delivered':
                        $campaignContact->status = 'delivered';
                        $campaignContact->delivered_at = now();
                        break;
                    case 'opened':
                        if ($campaignContact->status != 'clicked' && $campaignContact->status != 'responded') {
                            $campaignContact->status = 'opened';
                        }
                        $campaignContact->opened_at = now();
                        break;
                    case 'clicked':
                        if ($campaignContact->status != 'responded') {
                            $campaignContact->status = 'clicked';
                        }
                        $campaignContact->clicked_at = now();
                        break;
                    case 'bounce':
                        $campaignContact->status = 'bounced';
                        break;
                    case 'complaint':
                        // Handle complaints - might want to blacklist the contact
                        break;
                }

                $campaignContact->save();
            }

            // Update contact deal status for opened/clicked if not already in a more advanced state
            if (in_array($eventType, ['opened', 'clicked']) &&
                $contact->deal_status === 'none' &&
                ! $contact->has_been_contacted) {

                $contact->has_been_contacted = true;
                $contact->deal_status = 'contacted';
                $contact->save();
            }
        } catch (\Exception $e) {
            Log::error('Error recording email event: '.$e->getMessage());
        }
    }


    /**
     * Process unsubscribe request.
     */
    public function unsubscribe(Request $request, Campaign $campaign, Contact $contact)
    {
        // Verify the token
        $token = $request->query('token');

        // Verify the unsubscribe token is valid
        if (! $token || ! $this->verifyUnsubscribeToken($token, $campaign->id, $contact->id, $contact->email)) {
            return response('Invalid unsubscribe token', 403);
        }

        try {
            // Record the unsubscribe event
            $this->recordEvent($campaign, $contact, 'unsubscribed', $request);

            // Update all campaign contacts for this contact to prevent future emails
            CampaignContact::where('contact_id', $contact->id)
                ->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);

            // Update contact's record to mark as unsubscribed
            $contact->has_unsubscribed = true;
            $contact->unsubscribed_at = now();
            $contact->save();

            // Simple confirmation page
            return response()->view('unsubscribed', [
                'contact' => $contact,
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing unsubscribe request: '.$e->getMessage());

            return response('An error occurred while processing your unsubscribe request. Please try again later.', 500);
        }
    }

    /**
     * Verify an unsubscribe token.
     */
    protected function verifyUnsubscribeToken(string $token, int $campaignId, int $contactId, string $email): bool
    {
        $key = config('app.key');
        $data = $campaignId.'|'.$contactId.'|'.$email;
        $expectedToken = hash_hmac('sha256', $data, $key);

        return hash_equals($expectedToken, $token);
    }
}
