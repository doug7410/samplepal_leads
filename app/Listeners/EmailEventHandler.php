<?php

namespace App\Listeners;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\EmailEvent;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class EmailEventHandler
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        // Extract campaign_id and contact_id from the message's headers or metadata
        $message = $event->message;
        $headers = $message->getHeaders();
        
        // Debug log to help diagnose issues
        Log::debug('Processing message sent event', [
            'headers' => array_keys($headers->all()),
            'message_class' => get_class($message),
        ]);
        
        $campaignId = null;
        $contactId = null;
        
        // Try to extract from headers
        if ($headers->has('X-Campaign-ID')) {
            $header = $headers->get('X-Campaign-ID');
            // Handle both array or single object cases
            if (is_array($header) && isset($header[0])) {
                $campaignId = (int) $header[0]->getBodyAsString();
            } elseif (method_exists($header, 'getBodyAsString')) {
                $campaignId = (int) $header->getBodyAsString();
            }
        }
        
        if ($headers->has('X-Contact-ID')) {
            $header = $headers->get('X-Contact-ID');
            // Handle both array or single object cases
            if (is_array($header) && isset($header[0])) {
                $contactId = (int) $header[0]->getBodyAsString();
            } elseif (method_exists($header, 'getBodyAsString')) {
                $contactId = (int) $header->getBodyAsString();
            }
        }
        
        // If headers aren't available, try to extract from metadata or tags
        if ((!$campaignId || !$contactId) && method_exists($message, 'getMetadata')) {
            $metadata = $message->getMetadata();
            $campaignId = $metadata['campaign_id'] ?? $campaignId;
            $contactId = $metadata['contact_id'] ?? $contactId;
        }
        
        // Log the event
        if ($campaignId && $contactId) {
            Log::info("Message sent for campaign #{$campaignId} to contact #{$contactId}");
            
            try {
                // Find campaign and contact
                $campaign = Campaign::find($campaignId);
                $contact = Contact::find($contactId);
                
                if ($campaign && $contact) {
                    // Update campaign contact status
                    $campaignContact = CampaignContact::where('campaign_id', $campaignId)
                        ->where('contact_id', $contactId)
                        ->first();
                        
                    if ($campaignContact && $campaignContact->status === 'pending') {
                        $campaignContact->status = 'sent';
                        $campaignContact->sent_at = now();
                        $campaignContact->save();
                    }
                    
                    // Record sent event
                    $this->recordEvent($campaign, $contact, 'sent');
                }
            } catch (\Exception $e) {
                Log::error("Error processing message sent event: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Record an email event.
     */
    protected function recordEvent(Campaign $campaign, Contact $contact, string $eventType, array $eventData = []): void
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
            
            $event->save();
        } catch (\Exception $e) {
            Log::error('Error recording email event: ' . $e->getMessage());
        }
    }
}