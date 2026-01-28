<?php

namespace App\Console\Commands;

use App\Models\Sequence;
use App\Models\SequenceContact;
use App\Services\SequenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSequences extends Command
{
    protected $signature = 'sequences:process';

    protected $description = 'Process active sequences and send scheduled emails';

    public function handle(SequenceService $sequenceService): void
    {
        $this->info('Processing sequences...');

        $sequenceContacts = SequenceContact::where('status', SequenceContact::STATUS_ACTIVE)
            ->where('next_send_at', '<=', now())
            ->whereHas('sequence', function ($query) {
                $query->where('status', Sequence::STATUS_ACTIVE);
            })
            ->with(['sequence', 'contact'])
            ->get();

        if ($sequenceContacts->isEmpty()) {
            $this->info('No sequence contacts ready to process.');

            return;
        }

        $this->info("Found {$sequenceContacts->count()} sequence contacts to process.");

        $processed = 0;
        $exited = 0;

        foreach ($sequenceContacts as $sequenceContact) {
            $exitReason = $sequenceService->checkExitCriteria($sequenceContact);

            if ($exitReason) {
                $sequenceService->exitContact($sequenceContact, $exitReason);
                $this->info("Contact #{$sequenceContact->contact_id} exited sequence #{$sequenceContact->sequence_id}: {$exitReason}");
                Log::info("Sequence contact #{$sequenceContact->id} exited: {$exitReason}");
                $exited++;

                continue;
            }

            $result = $sequenceService->processSequenceContact($sequenceContact);

            if ($result) {
                $this->info("Processed contact #{$sequenceContact->contact_id} in sequence #{$sequenceContact->sequence_id}");
                Log::info("Sequence contact #{$sequenceContact->id} processed, email job dispatched.");
                $processed++;
            }
        }

        $this->info("Done. Processed: {$processed}, Exited: {$exited}");
    }
}
