<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class EmailTrackingController extends Controller
{
    protected MailService $mailService;
    
    /**
     * Constructor
     */
    public function __construct(MailService $mailService)
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
        if (!$this->mailService->verifyTrackingToken($token, $campaign->id, $contact->id)) {
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
    public function trackClick(Request $request, Campaign $campaign, Contact $contact): Response
    {
        $token = $request->query('token');
        $encodedUrl = $request->query('url');
        
        // Validate the token
        if (!$this->mailService->verifyTrackingToken($token, $campaign->id, $contact->id)) {
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
            Log::error('Error tracking click: ' . $e->getMessage());
            return response('Error processing link', 500);
        }
    }
    
    /**
     * Handle Resend webhook notification.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();
        
        // Verify the webhook signature if a secret is configured
        $webhookSecret = config('services.resend.webhook_secret');
        if ($webhookSecret) {
            $signature = $request->header('Resend-Signature');
            if (!$signature || !$this->verifyResendSignature($payload, $signature, $webhookSecret)) {
                return response('Invalid signature', 403);
            }
        }
        
        // Verify it's a valid Resend event
        if (!isset($payload['type'])) {
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
        
        if (isset($headers['X-Campaign-ID'])) {
            $campaignId = (int) $headers['X-Campaign-ID'];
        }
        
        if (isset($headers['X-Contact-ID'])) {
            $contactId = (int) $headers['X-Contact-ID'];
        }
        
        // If we have campaign and contact IDs, record the event
        if ($campaignId && $contactId) {
            $campaign = Campaign::find($campaignId);
            $contact = Contact::find($contactId);
            
            if ($campaign && $contact) {
                $eventData = [
                    'message_id' => $data['id'] ?? null,
                    'resend_data' => $data,
                ];
                
                $this->recordEvent($campaign, $contact, $event, null, $eventData);
            }
        }
        
        return response('OK', 200);
    }
    
    /**
     * Verify Resend webhook signature.
     */
    protected function verifyResendSignature(array $payload, string $signature, string $secret): bool
    {
        try {
            // Split timestamp and signature parts
            [$timestamp, $signaturePart] = explode(',', $signature);
            $timestamp = substr($timestamp, 2); // Remove 't='
            $signaturePart = substr($signaturePart, 2); // Remove 's='
            
            // Recreate the signed payload string
            $signedPayload = $timestamp . '.' . json_encode($payload);
            
            // Calculate the expected signature
            $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
            
            // Verify the signature
            return hash_equals($expectedSignature, $signaturePart);
        } catch (\Exception $e) {
            Log::error('Error verifying Resend signature: ' . $e->getMessage());
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
                !$contact->has_been_contacted) {
                
                $contact->has_been_contacted = true;
                $contact->deal_status = 'contacted';
                $contact->save();
            }
        } catch (\Exception $e) {
            Log::error('Error recording email event: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark contact as responded.
     */
    public function markAsResponded(Campaign $campaign, Contact $contact): Response
    {
        try {
            // Record the response event
            $this->recordEvent($campaign, $contact, 'responded', request());
            
            // Update campaign contact
            $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->first();
                
            if ($campaignContact) {
                $campaignContact->status = 'responded';
                $campaignContact->responded_at = now();
                $campaignContact->save();
            }
            
            // Update contact deal status
            if (in_array($contact->deal_status, ['none', 'contacted'])) {
                $contact->deal_status = 'responded';
                $contact->save();
            }
            
            return response('Success', 200);
        } catch (\Exception $e) {
            Log::error('Error marking contact as responded: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
}
