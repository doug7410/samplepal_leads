<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Sequence;
use App\Models\SequenceStep;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class SequenceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sequence $sequence,
        public SequenceStep $step,
        public Contact $contact,
        public string $htmlContent,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $replyTo = null
    ) {}

    public function envelope(): Envelope
    {
        $fromEmail = $this->fromEmail ?? Config::get('mail.from.address');
        $fromName = $this->fromName ?? Config::get('mail.from.name');
        $replyTo = $this->replyTo ?? $fromEmail;

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromEmail, $fromName),
            replyTo: [new \Illuminate\Mail\Mailables\Address($replyTo)],
            subject: $this->parseTemplate($this->step->subject, $this->contact),
            tags: ['sequence_id' => (string) $this->sequence->id, 'contact_id' => (string) $this->contact->id],
            metadata: [
                'sequence_id' => $this->sequence->id,
                'sequence_step_id' => $this->step->id,
                'contact_id' => $this->contact->id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }

    public function attachments(): array
    {
        return [];
    }

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
}
