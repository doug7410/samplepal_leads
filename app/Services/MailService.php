<?php

namespace App\Services;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailService extends AbstractMailService
{
    /**
     * Deliver the email using Laravel's Mail facade
     *
     * @param  Campaign  $campaign  Campaign containing email content and subject
     * @param  Contact  $contact  Contact to send email to
     * @param  string  $subject  Processed email subject
     * @param  string  $htmlBody  Processed HTML body with tracking elements
     * @param  array  $options  Additional options for email sending
     * @return string|null Message ID on success, null on failure
     */
    protected function deliverEmail(
        Campaign $campaign,
        Contact $contact,
        string $subject,
        string $htmlBody,
        array $options
    ): ?string {
        // Create a new mailable instance with the processed content
        $mailable = new CampaignMail($campaign, $contact, $htmlBody);
        $mailable->subject($subject);

        // Add the tracking headers to the email
        $this->addTrackingHeaders($mailable, $campaign, $contact);

        // Send the email using Laravel's Mail facade with configured mailer
        $messageId = Str::uuid()->toString(); // Fallback ID
        $emailSent = false;

        try {
            Log::info("Attempting to send email to {$contact->email} using ".config('mail.default').' mailer');

            // Use the default mailer configured in .env
            Mail::to($contact->email)
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
                Log::warning('Could not get last message ID: '.$e->getMessage());
                // Continue using the fallback ID
            }

            Log::info("Email sent successfully to {$contact->email}");

            return $messageId;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Error sending email via '.config('mail.default').': '.$errorMessage);
            
            // Check for rate limit errors from Resend
            if (strpos($errorMessage, 'Too many requests') !== false) {
                // Since we can't directly release the job here, we'll just log the error
                Log::warning("Resend rate limit reached. The job will be retried with backoff.");
                throw $e; // Re-throw to trigger the job retry with backoff defined in the job class
            }

            // Always attempt fallback to log mailer if it's not a rate limit error
            try {
                Log::info("Attempting fallback to log mailer for {$contact->email}");

                Mail::mailer('log')
                    ->to($contact->email)
                    ->send($mailable);

                // If we get here, the fallback email was sent successfully
                Log::info('Fallback email sent successfully to log');

                return $messageId; // Return the generated ID
            } catch (\Exception $fallbackEx) {
                Log::error('Fallback mailer also failed: '.$fallbackEx->getMessage());

                return null;
            }

            return null;
        }
    }

    /**
     * Add campaign and contact tracking headers to the email
     */
    protected function addTrackingHeaders($mailable, Campaign $campaign, Contact $contact): void
    {
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

                    // Log the headers added
                    $this->logAddedHeaders($message, $campaign, $contact);
                } catch (\Exception $e) {
                    Log::error('Error adding headers to email: '.$e->getMessage());
                }
            });
        } catch (\Exception $e) {
            Log::error('Error configuring email message: '.$e->getMessage());
            // Continue without header configuration rather than failing completely
        }
    }

    /**
     * Log the headers that were added to the message
     */
    protected function logAddedHeaders($message, Campaign $campaign, Contact $contact): void
    {
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
            Log::warning('Could not log email headers: '.$headerEx->getMessage());
        }
    }

    /**
     * Determine if we should attempt a fallback mailer - kept for backwards compatibility
     * but no longer used since we always attempt fallback
     */
    protected function shouldAttemptFallback(): bool
    {
        return true;
    }
}
