<?php

namespace App\Jobs;

use App\Models\CampaignContact;
use App\Models\CampaignSegment;
use App\Services\CampaignSegmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCampaignSegmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $batchSize = 50;

    public function __construct(protected CampaignSegment $segment) {}

    public function handle(): void
    {
        $campaign = $this->segment->campaign;

        if (! $campaign->canProcess()) {
            Log::info("Campaign #{$campaign->id} is not in a processable state. Skipping segment #{$this->segment->id}.");

            return;
        }

        if ($this->segment->status !== CampaignSegment::STATUS_IN_PROGRESS) {
            Log::info("Segment #{$this->segment->id} is not in progress. Skipping.");

            return;
        }

        $pendingCount = CampaignContact::where('campaign_id', $campaign->id)
            ->where('campaign_segment_id', $this->segment->id)
            ->where('status', CampaignContact::STATUS_PENDING)
            ->count();

        if ($pendingCount === 0) {
            Log::info("Segment #{$this->segment->id} has no pending emails. Completing.");
            app(CampaignSegmentService::class)->completeSegment($this->segment);

            return;
        }

        $pending = CampaignContact::where('campaign_id', $campaign->id)
            ->where('campaign_segment_id', $this->segment->id)
            ->where('status', CampaignContact::STATUS_PENDING)
            ->take($this->batchSize)
            ->get();

        Log::info("Processing {$pending->count()} emails for segment #{$this->segment->id} of campaign #{$campaign->id}");

        foreach ($pending as $campaignContact) {
            SendCampaignEmailJob::dispatch($campaignContact);
        }

        if ($pendingCount > $this->batchSize) {
            self::dispatch($this->segment)->delay(now()->addMinutes(1));
        }
    }
}
