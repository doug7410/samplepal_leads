<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Jobs\SendSequenceEmailJob;
use App\Models\Contact;
use App\Models\Sequence;
use App\Models\SequenceContact;
use App\Models\SequenceEmail;
use App\Models\SequenceStep;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    public function createSequence(array $data): Sequence
    {
        return DB::transaction(function () use ($data) {
            $sequence = Sequence::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'user_id' => $data['user_id'],
                'status' => Sequence::STATUS_DRAFT,
                'entry_filter' => $data['entry_filter'] ?? null,
            ]);

            if (! empty($data['steps'])) {
                foreach ($data['steps'] as $index => $stepData) {
                    $sequence->steps()->create([
                        'step_order' => $index,
                        'name' => $stepData['name'],
                        'subject' => $stepData['subject'],
                        'content' => $stepData['content'],
                        'delay_days' => $stepData['delay_days'] ?? 0,
                        'send_time' => $stepData['send_time'] ?? null,
                    ]);
                }
            }

            return $sequence;
        });
    }

    public function updateSequence(Sequence $sequence, array $data): Sequence
    {
        return DB::transaction(function () use ($sequence, $data) {
            $sequence->update([
                'name' => $data['name'] ?? $sequence->name,
                'description' => $data['description'] ?? $sequence->description,
                'entry_filter' => $data['entry_filter'] ?? $sequence->entry_filter,
            ]);

            if (isset($data['steps'])) {
                $sequence->steps()->delete();

                foreach ($data['steps'] as $index => $stepData) {
                    $sequence->steps()->create([
                        'step_order' => $index,
                        'name' => $stepData['name'],
                        'subject' => $stepData['subject'],
                        'content' => $stepData['content'],
                        'delay_days' => $stepData['delay_days'] ?? 0,
                        'send_time' => $stepData['send_time'] ?? null,
                    ]);
                }
            }

            return $sequence->fresh(['steps']);
        });
    }

    public function deleteSequence(Sequence $sequence): bool
    {
        return DB::transaction(function () use ($sequence) {
            return $sequence->delete();
        });
    }

    public function addContactsToSequence(Sequence $sequence, array $contactIds): int
    {
        return DB::transaction(function () use ($sequence, $contactIds) {
            $contacts = Contact::whereIn('id', $contactIds)
                ->whereHas('company')
                ->whereNotNull('email')
                ->where('has_unsubscribed', false)
                ->where('deal_status', '!=', DealStatus::ClosedWon)
                ->whereNotIn('id', function ($query) use ($sequence) {
                    $query->select('contact_id')
                        ->from('sequence_contacts')
                        ->where('sequence_id', $sequence->id);
                })
                ->get();

            $firstStep = $sequence->steps()->orderBy('step_order')->first();
            $now = now();

            foreach ($contacts as $contact) {
                $nextSendAt = $this->calculateNextSendAt($firstStep, $now);

                SequenceContact::create([
                    'sequence_id' => $sequence->id,
                    'contact_id' => $contact->id,
                    'current_step' => 0,
                    'status' => SequenceContact::STATUS_ACTIVE,
                    'next_send_at' => $nextSendAt,
                    'entered_at' => $now,
                ]);
            }

            return $contacts->count();
        });
    }

    public function removeContactFromSequence(Sequence $sequence, int $contactId): bool
    {
        return DB::transaction(function () use ($sequence, $contactId) {
            $sequenceContact = SequenceContact::where('sequence_id', $sequence->id)
                ->where('contact_id', $contactId)
                ->first();

            if (! $sequenceContact) {
                return false;
            }

            if ($sequenceContact->status === SequenceContact::STATUS_ACTIVE) {
                $sequenceContact->update([
                    'status' => SequenceContact::STATUS_EXITED,
                    'exited_at' => now(),
                    'exit_reason' => SequenceContact::EXIT_REASON_MANUAL,
                ]);
            }

            return true;
        });
    }

    public function activateSequence(Sequence $sequence): bool
    {
        if ($sequence->steps()->count() === 0) {
            return false;
        }

        return $sequence->update(['status' => Sequence::STATUS_ACTIVE]);
    }

    public function pauseSequence(Sequence $sequence): bool
    {
        return $sequence->update(['status' => Sequence::STATUS_PAUSED]);
    }

    public function checkExitCriteria(SequenceContact $sequenceContact): ?string
    {
        $contact = $sequenceContact->contact;

        if ($contact->deal_status === DealStatus::ClosedWon) {
            return SequenceContact::EXIT_REASON_CONVERTED;
        }

        if ($contact->has_unsubscribed) {
            return SequenceContact::EXIT_REASON_UNSUBSCRIBED;
        }

        return null;
    }

    public function processSequenceContact(SequenceContact $sequenceContact): bool
    {
        $sequence = $sequenceContact->sequence;

        if ($sequence->status !== Sequence::STATUS_ACTIVE) {
            return false;
        }

        $exitReason = $this->checkExitCriteria($sequenceContact);
        if ($exitReason) {
            $this->exitContact($sequenceContact, $exitReason);

            return false;
        }

        $step = $sequence->steps()->where('step_order', $sequenceContact->current_step)->first();

        if (! $step) {
            $this->completeContact($sequenceContact);

            return false;
        }

        $sequenceEmail = SequenceEmail::create([
            'sequence_contact_id' => $sequenceContact->id,
            'sequence_step_id' => $step->id,
            'status' => SequenceEmail::STATUS_PENDING,
        ]);

        SendSequenceEmailJob::dispatch($sequenceEmail);

        return true;
    }

    public function advanceContact(SequenceContact $sequenceContact): void
    {
        $sequence = $sequenceContact->sequence;
        $nextStepOrder = $sequenceContact->current_step + 1;
        $nextStep = $sequence->steps()->where('step_order', $nextStepOrder)->first();

        if (! $nextStep) {
            $this->completeContact($sequenceContact);

            return;
        }

        $nextSendAt = $this->calculateNextSendAt($nextStep, now());

        $sequenceContact->update([
            'current_step' => $nextStepOrder,
            'next_send_at' => $nextSendAt,
        ]);
    }

    public function exitContact(SequenceContact $sequenceContact, string $reason): void
    {
        $sequenceContact->update([
            'status' => SequenceContact::STATUS_EXITED,
            'exited_at' => now(),
            'exit_reason' => $reason,
            'next_send_at' => null,
        ]);
    }

    public function completeContact(SequenceContact $sequenceContact): void
    {
        $sequenceContact->update([
            'status' => SequenceContact::STATUS_COMPLETED,
            'next_send_at' => null,
        ]);
    }

    public function getStatistics(Sequence $sequence): array
    {
        $sequenceContacts = $sequence->sequenceContacts;

        $statusCounts = $sequenceContacts->groupBy('status')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $exitReasonCounts = $sequenceContacts->where('status', 'exited')
            ->groupBy('exit_reason')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $stepStats = [];
        $steps = $sequence->steps;

        foreach ($steps as $step) {
            $emails = SequenceEmail::where('sequence_step_id', $step->id)->get();
            $stepStats[$step->step_order] = [
                'name' => $step->name,
                'sent' => $emails->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->count(),
                'delivered' => $emails->whereIn('status', ['delivered', 'opened', 'clicked'])->count(),
                'opened' => $emails->whereIn('status', ['opened', 'clicked'])->count(),
                'clicked' => $emails->where('status', 'clicked')->count(),
                'bounced' => $emails->where('status', 'bounced')->count(),
                'failed' => $emails->where('status', 'failed')->count(),
            ];
        }

        return [
            'total_enrolled' => $sequenceContacts->count(),
            'active' => $statusCounts['active'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'exited' => $statusCounts['exited'] ?? 0,
            'exit_reasons' => $exitReasonCounts,
            'step_stats' => $stepStats,
        ];
    }

    protected function calculateNextSendAt(?SequenceStep $step, $baseTime): ?\DateTime
    {
        if (! $step) {
            return null;
        }

        $nextSendAt = $baseTime->copy()->addDays($step->delay_days);

        if ($step->send_time) {
            $nextSendAt->setTimeFromTimeString($step->send_time);
            if ($nextSendAt->isPast()) {
                $nextSendAt->addDay();
            }
        }

        return $nextSendAt;
    }
}
