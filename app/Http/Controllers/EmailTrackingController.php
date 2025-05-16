<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Services\SesMailService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class EmailTrackingController extends Controller
{
    protected SesMailService $mailService;
    
    /**
     * Constructor
     */
    public function __construct(SesMailService $mailService)
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
     * Handle SES webhook (SNS notification).
     */
    public function handleSesWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);
        
        // Verify it's from SNS
        if (!isset($data['Type'])) {
            return response('Invalid payload', 400);
        }
        
        // Handle SNS subscription confirmation
        if ($data['Type'] === 'SubscriptionConfirmation') {
            // Automatically confirm the subscription
            if (isset($data['SubscribeURL'])) {
                file_get_contents($data['SubscribeURL']);
                return response('Subscription confirmed', 200);
            }
        }
        
        // Handle SES notification
        if ($data['Type'] === 'Notification') {
            $message = json_decode($data['Message'], true);
            
            // Skip if not an SES event
            if (!isset($message['eventType'])) {
                return response('Not an SES event', 200);
            }
            
            // Process the event
            $event = $message['eventType'];
            $mail = $message['mail'] ?? [];
            
            // Get tracking data from tags
            $tags = $mail['tags'] ?? [];
            $campaignId = null;
            $contactId = null;
            
            foreach ($tags as $tagName => $tagValues) {
                if ($tagName === 'campaign_id' && !empty($tagValues)) {
                    $campaignId = (int) $tagValues[0];
                }
                if ($tagName === 'contact_id' && !empty($tagValues)) {
                    $contactId = (int) $tagValues[0];
                }
            }
            
            // If we have campaign and contact IDs, record the event
            if ($campaignId && $contactId) {
                $campaign = Campaign::find($campaignId);
                $contact = Contact::find($contactId);
                
                if ($campaign && $contact) {
                    $eventData = [
                        'message_id' => $mail['messageId'] ?? null,
                    ];
                    
                    if (isset($message['delivery'])) {
                        $eventData['delivery'] = $message['delivery'];
                    } elseif (isset($message['bounce'])) {
                        $eventData['bounce'] = $message['bounce'];
                    } elseif (isset($message['complaint'])) {
                        $eventData['complaint'] = $message['complaint'];
                    }
                    
                    $this->recordEvent($campaign, $contact, $event, null, $eventData);
                }
            }
        }
        
        return response('OK', 200);
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
