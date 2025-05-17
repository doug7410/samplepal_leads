<?php

namespace App\Services;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailService
{
    /**
     * Send an email using Laravel's built-in Mail facade.
     *
     * @param Campaign $campaign
     * @param Contact $contact
     * @param array $options
     * @return string|null Message ID on success, null on failure
     */
    public function sendEmail(Campaign $campaign, Contact $contact, array $options = []): ?string
    {
        try {
            // Make sure we have the proper contact id, since we're receiving objects that may be stale
            if (!$contact->id) {
                Log::error("Contact has no ID: " . json_encode($contact->toArray()));
                throw new \Exception("Contact has no ID");
            }
            
            // Get the campaign contact record
            $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->first();
                
            if (!$campaignContact) {
                Log::error("Campaign contact record not found for campaign #{$campaign->id} and contact #{$contact->id}");
                
                // Log more details to help diagnose the issue
                $existingCount = CampaignContact::where('campaign_id', $campaign->id)->count();
                Log::error("Campaign has {$existingCount} contacts total");
                
                throw new \Exception("Campaign contact record not found");
            }
            
            // Check if campaign contact is already being processed or has been processed
            if ($campaignContact->status !== CampaignContact::STATUS_PENDING) {
                Log::warning("Campaign contact #{$campaignContact->id} has status '{$campaignContact->status}', not processing again");
                return null;
            }
            
            // Mark as processing to prevent duplicate processing
            $campaignContact->status = CampaignContact::STATUS_PROCESSING;
            $campaignContact->save();
            
            // Ensure the status was saved correctly
            if ($campaignContact->fresh()->status !== CampaignContact::STATUS_PROCESSING) {
                Log::error("Failed to update campaign contact #{$campaignContact->id} status to 'processing'. Current status: " . $campaignContact->fresh()->status);
                throw new \Exception("Failed to update campaign contact status");
            }
        } catch (\Exception $e) {
            Log::error("Failed to prepare campaign contact: " . $e->getMessage());
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
            
            // Create a new mailable instance with the processed content
            $mailable = new CampaignMail($campaign, $contact, $htmlBody);
            
            // Add Resend-specific headers if needed
            try {
                $mailable->withSymfonyMessage(function ($message) use ($campaign, $contact) {
                    try {
                        // Add X-Campaign-ID and X-Contact-ID headers for tracking
                        $message->getHeaders()->addTextHeader('X-Campaign-ID', (string) $campaign->id);
                        $message->getHeaders()->addTextHeader('X-Contact-ID', (string) $contact->id);
                        
                        // Add message stream ID if configured
                        if (Config::has('services.resend.message_stream_id')) {
                            $message->getHeaders()->addTextHeader(
                                'X-Message-Stream',
                                Config::get('services.resend.message_stream_id')
                            );
                        }
                        
                        // Log the headers added - safely convert headers to array
                        try {
                            $headers = $message->getHeaders()->all();
                            // Handle both array and iterator
                            $headerNames = [];
                            if (is_array($headers)) {
                                $headerNames = array_keys($headers);
                            } else {
                                // Safely iterate through headers
                                foreach ($headers as $name => $header) {
                                    $headerNames[] = $name;
                                }
                            }
                            
                            Log::debug('Added headers to email', [
                                'campaign_id' => $campaign->id,
                                'contact_id' => $contact->id,
                                'headers' => $headerNames,
                            ]);
                        } catch (\Exception $headerEx) {
                            // Catch any issues with headers but don't interrupt
                            Log::warning('Could not log email headers: ' . $headerEx->getMessage());
                        }
                    } catch (\Exception $e) {
                        Log::error('Error adding headers to email: ' . $e->getMessage());
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error configuring email message: ' . $e->getMessage());
                // Continue without header configuration rather than failing completely
            }
            
            // Send the email using Laravel's Mail facade with Resend
            $messageId = Str::uuid()->toString(); // Fallback ID
            
            $emailSent = false;
            
            try {
                Log::info("Attempting to send email to {$contact->email} using " . config('mail.default') . " mailer");
                
                // Use the failover mailer to ensure we try multiple options in sequence
                Mail::mailer('failover')
                    ->to($contact->email)
                    ->send($mailable);
                
                // If we get here, the email was sent successfully
                $emailSent = true;
                
                // Get the message ID from the last sent message if available
                try {
                    if (method_exists(Mail::getSymfonyTransport(), 'getLastMessageId')) {
                        $lastMessageId = Mail::getSymfonyTransport()->getLastMessageId();
                        if ($lastMessageId) {
                            $messageId = $lastMessageId;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not get last message ID: " . $e->getMessage());
                    // Continue using the fallback ID
                }
                
                Log::info("Email sent successfully to {$contact->email}");
            } catch (\Exception $e) {
                Log::error("Error sending email via " . config('mail.default') . ": " . $e->getMessage());
                
                // If we're using Resend and it fails, try falling back to a different mailer
                if (config('mail.default') === 'resend') {
                    try {
                        Log::info("Attempting fallback to log mailer for {$contact->email}");
                        
                        Mail::mailer('log')
                            ->to($contact->email)
                            ->send($mailable);
                        
                        // If we get here, the fallback email was sent successfully    
                        $emailSent = true;
                        
                        Log::info("Fallback email sent successfully to log");
                    } catch (\Exception $fallbackEx) {
                        Log::error("Fallback mailer also failed: " . $fallbackEx->getMessage());
                        throw $fallbackEx; // Re-throw to be caught by the outer catch
                    }
                } else {
                    throw $e; // Re-throw if we're not using Resend or to be caught by the outer catch
                }
            }
            
            // If the email was sent, we should update the status regardless of any other errors
            if ($emailSent) {
                try {
                    // Save the message ID for tracking (make sure we're updating the same record we marked as processing)
                    $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                        ->where('contact_id', $contact->id)
                        ->whereIn('status', [CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PROCESSING])
                        ->first();
                        
                    if ($campaignContact) {
                        $campaignContact->message_id = $messageId;
                        $campaignContact->status = CampaignContact::STATUS_SENT;
                        $campaignContact->sent_at = now();
                        $campaignContact->save();
                        
                        Log::info("Updated campaign contact #{$campaignContact->id} status to sent");
                    } else {
                        Log::warning("Campaign contact for campaign #{$campaign->id} and contact #{$contact->id} not found, could not update status");
                    }
                } catch (\Exception $e) {
                    // Don't let status update errors cause the whole email sending to fail
                    Log::error("Error updating campaign contact status after email was sent: " . $e->getMessage());
                }
            } else {
                Log::error("Email to {$contact->email} was not sent due to errors");
            }
            
            return $messageId;
        } catch (\Exception $e) {
            // Log the error and update the campaign contact
            Log::error("Failed to send email to {$contact->email}: " . $e->getMessage());
            
            // Find the campaign contact record that should be in processing state
            $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->whereIn('status', [CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PROCESSING]) // Handle both pending and processing
                ->first();
                
            if ($campaignContact) {
                $campaignContact->status = CampaignContact::STATUS_FAILED;
                $campaignContact->failed_at = now();
                $campaignContact->failure_reason = $e->getMessage();
                $campaignContact->save();
                
                Log::info("Marked campaign contact #{$campaignContact->id} as failed");
            } else {
                Log::warning("Could not find pending or processing campaign contact for campaign #{$campaign->id} and contact #{$contact->id}");
            }
            
            return null;
        }
    }
    
    /**
     * Parse template variables in content.
     *
     * @param string $content
     * @param Contact $contact
     * @return string
     */
    protected function parseTemplate(string $content, Contact $contact): string
    {
        $replacements = [
            '{{first_name}}' => $contact->first_name,
            '{{last_name}}' => $contact->last_name,
            '{{full_name}}' => trim($contact->first_name . ' ' . $contact->last_name),
            '{{email}}' => $contact->email,
            '{{company}}' => optional($contact->company)->name ?? '',
            '{{job_title}}' => $contact->job_title ?? '',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Generate a tracking pixel for email opens.
     *
     * @param int $campaignId
     * @param int $contactId
     * @return string
     */
    protected function getTrackingPixel(int $campaignId, int $contactId): string
    {
        $trackingUrl = route('email.track.open', [
            'campaign' => $campaignId,
            'contact' => $contactId,
            'token' => $this->generateTrackingToken($campaignId, $contactId),
        ]);
        
        return '<img src="' . $trackingUrl . '" alt="" width="1" height="1" style="display:none;" />';
    }
    
    /**
     * Process links in the email content to add click tracking.
     *
     * @param string $content
     * @param int $campaignId
     * @param int $contactId
     * @return string
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
     *
     * @param int $campaignId
     * @param int $contactId
     * @return string
     */
    protected function generateTrackingToken(int $campaignId, int $contactId): string
    {
        $key = config('app.key');
        return hash_hmac('sha256', "campaign:{$campaignId},contact:{$contactId}", $key);
    }
    
    /**
     * Verify a tracking token.
     *
     * @param string $token
     * @param int $campaignId
     * @param int $contactId
     * @return bool
     */
    public function verifyTrackingToken(string $token, int $campaignId, int $contactId): bool
    {
        $expectedToken = $this->generateTrackingToken($campaignId, $contactId);
        return hash_equals($expectedToken, $token);
    }
}