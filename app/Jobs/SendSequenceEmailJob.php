<?php

namespace App\Jobs;

use App\Jobs\Middleware\RateLimitEmailJobs;
use App\Mail\SequenceMail;
use App\Models\Sequence;
use App\Models\SequenceContact;
use App\Models\SequenceEmail;
use App\Services\SequenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendSequenceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SequenceEmail $sequenceEmail;

    public function __construct(SequenceEmail $sequenceEmail)
    {
        $this->sequenceEmail = $sequenceEmail;
    }

    public function middleware(): array
    {
        return [new RateLimitEmailJobs];
    }

    public function handle(SequenceService $sequenceService): void
    {
        $sequenceEmail = $this->sequenceEmail->fresh(['sequenceContact.contact', 'sequenceContact.sequence', 'sequenceStep']);

        if (! $sequenceEmail) {
            Log::error('SequenceEmail not found');

            return;
        }

        $sequenceContact = $sequenceEmail->sequenceContact;
        $sequence = $sequenceContact->sequence;
        $contact = $sequenceContact->contact;
        $step = $sequenceEmail->sequenceStep;

        if ($sequence->status !== Sequence::STATUS_ACTIVE) {
            Log::info("Sequence #{$sequence->id} is not active. Skipping email.");

            return;
        }

        if ($sequenceContact->status !== SequenceContact::STATUS_ACTIVE) {
            Log::info("Sequence contact #{$sequenceContact->id} is not active. Skipping email.");

            return;
        }

        if ($sequenceEmail->status !== SequenceEmail::STATUS_PENDING) {
            Log::info("Sequence email #{$sequenceEmail->id} already processed with status: {$sequenceEmail->status}");

            return;
        }

        $exitReason = $sequenceService->checkExitCriteria($sequenceContact);
        if ($exitReason) {
            $sequenceService->exitContact($sequenceContact, $exitReason);
            Log::info("Contact #{$contact->id} exited sequence #{$sequence->id}: {$exitReason}");

            return;
        }

        try {
            $htmlContent = $this->parseTemplate($step->content, $contact);

            $mailable = new SequenceMail(
                $sequence,
                $step,
                $contact,
                $htmlContent
            );

            $this->addTrackingHeaders($mailable, $sequence, $step, $contact);

            $messageId = Str::uuid()->toString();

            Mail::to($contact->email)->send($mailable);

            try {
                if (method_exists(Mail::getSymfonyTransport(), 'getLastMessageId')) {
                    $lastMessageId = Mail::getSymfonyTransport()->getLastMessageId();
                    if ($lastMessageId) {
                        $messageId = $lastMessageId;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not get last message ID: '.$e->getMessage());
            }

            $sequenceEmail->update([
                'status' => SequenceEmail::STATUS_SENT,
                'sent_at' => now(),
                'message_id' => $messageId,
            ]);

            Log::info("Sequence email sent to {$contact->email}, Message ID: {$messageId}");

            $sequenceService->advanceContact($sequenceContact);

        } catch (\Exception $e) {
            Log::error("Failed to send sequence email to {$contact->email}: ".$e->getMessage());

            $sequenceEmail->update([
                'status' => SequenceEmail::STATUS_FAILED,
            ]);
        }
    }

    protected function parseTemplate(string $content, $contact): string
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

    protected function addTrackingHeaders($mailable, $sequence, $step, $contact): void
    {
        try {
            $mailable->withSymfonyMessage(function ($message) use ($sequence, $step, $contact) {
                $message->getHeaders()->addTextHeader('X-Sequence-ID', (string) $sequence->id);
                $message->getHeaders()->addTextHeader('X-Sequence-Step-ID', (string) $step->id);
                $message->getHeaders()->addTextHeader('X-Contact-ID', (string) $contact->id);
            });
        } catch (\Exception $e) {
            Log::error('Error adding headers to sequence email: '.$e->getMessage());
        }
    }
}
