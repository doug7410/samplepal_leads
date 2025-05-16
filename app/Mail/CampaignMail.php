<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Contact $contact,
        public string $htmlContent
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromEmail = $this->campaign->from_email ?? Config::get('mail.from.address');
        $fromName = $this->campaign->from_name ?? Config::get('mail.from.name');
        $replyTo = $this->campaign->reply_to ?? $fromEmail;
        
        $envelope = new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromEmail, $fromName),
            replyTo: [new \Illuminate\Mail\Mailables\Address($replyTo)],
            subject: $this->parseTemplate($this->campaign->subject, $this->contact),
            tags: ['campaign_id' => (string) $this->campaign->id, 'contact_id' => (string) $this->contact->id],
            metadata: [
                'campaign_id' => $this->campaign->id,
                'contact_id' => $this->contact->id,
            ],
        );
        
        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
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
}