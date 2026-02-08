<?php

namespace App\Services;

use App\Jobs\ProcessCampaignSegmentJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CampaignSegment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignSegmentService
{
    /**
     * @return Collection<int, CampaignSegment>
     */
    public function createSegments(Campaign $campaign, int $numberOfSegments): Collection
    {
        return DB::transaction(function () use ($campaign, $numberOfSegments) {
            // Delete existing segments if any
            $this->deleteSegments($campaign, force: true);

            // Create segment records
            $segments = collect();
            for ($i = 1; $i <= $numberOfSegments; $i++) {
                $segments->push(CampaignSegment::create([
                    'campaign_id' => $campaign->id,
                    'name' => "Segment {$i}",
                    'position' => $i,
                    'status' => CampaignSegment::STATUS_DRAFT,
                ]));
            }

            // Distribute contacts round-robin
            $contacts = $campaign->campaignContacts()->orderBy('id')->get();
            $contacts->each(function (CampaignContact $contact, int $index) use ($segments) {
                $segmentIndex = $index % $segments->count();
                $contact->campaign_segment_id = $segments[$segmentIndex]->id;
                $contact->save();
            });

            return $segments;
        });
    }

    public function updateSegment(CampaignSegment $segment, array $data): CampaignSegment
    {
        return DB::transaction(function () use ($segment, $data) {
            $segment->update(array_intersect_key($data, array_flip(['name', 'subject', 'content'])));

            return $segment->fresh();
        });
    }

    public function deleteSegments(Campaign $campaign, bool $force = false): bool
    {
        return DB::transaction(function () use ($campaign, $force) {
            if (! $force) {
                $hasNonDraft = $campaign->segments()
                    ->where('status', '!=', CampaignSegment::STATUS_DRAFT)
                    ->exists();

                if ($hasNonDraft) {
                    return false;
                }
            }

            // Clear segment references from contacts
            CampaignContact::where('campaign_id', $campaign->id)
                ->whereNotNull('campaign_segment_id')
                ->update(['campaign_segment_id' => null]);

            // Delete all segments
            $campaign->segments()->delete();

            return true;
        });
    }

    /**
     * @return array{total: int, statuses: array<string, int>, rates: array<string, float>}
     */
    public function getSegmentStatistics(CampaignSegment $segment): array
    {
        $statusCounts = CampaignContact::where('campaign_segment_id', $segment->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $allStatuses = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'responded' => 0,
            'bounced' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'unsubscribed' => 0,
            'demo_scheduled' => 0,
        ];

        $statuses = array_merge($allStatuses, $statusCounts);
        $total = array_sum($statuses);

        $deliveredCount = $statuses['delivered'] + $statuses['opened'] + $statuses['clicked'] + $statuses['responded'];
        $deliveryRate = $total > 0 ? round(($deliveredCount / $total) * 100, 2) : 0;
        $clickRate = $deliveredCount > 0 ? round(($statuses['clicked'] / $deliveredCount) * 100, 2) : 0;

        return [
            'total' => $total,
            'statuses' => $statuses,
            'rates' => [
                'delivery' => $deliveryRate,
                'click' => $clickRate,
            ],
        ];
    }

    public function sendSegment(CampaignSegment $segment): bool
    {
        return DB::transaction(function () use ($segment) {
            $campaign = $segment->campaign;

            // Transition campaign to in_progress if it's still draft
            if ($campaign->status === Campaign::STATUS_DRAFT) {
                $campaign->status = Campaign::STATUS_IN_PROGRESS;
                $campaign->save();
            }

            $segment->status = CampaignSegment::STATUS_IN_PROGRESS;
            $segment->sent_at = now();
            $segment->save();

            ProcessCampaignSegmentJob::dispatch($segment);

            return true;
        });
    }

    public function completeSegment(CampaignSegment $segment): void
    {
        DB::transaction(function () use ($segment) {
            $statusCounts = $segment->campaignContacts()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalCount = array_sum($statusCounts);
            $failedCount = $statusCounts[CampaignContact::STATUS_FAILED] ?? 0;
            $sentCount = ($statusCounts[CampaignContact::STATUS_SENT] ?? 0)
                + ($statusCounts[CampaignContact::STATUS_DELIVERED] ?? 0)
                + ($statusCounts[CampaignContact::STATUS_OPENED] ?? 0)
                + ($statusCounts[CampaignContact::STATUS_CLICKED] ?? 0)
                + ($statusCounts[CampaignContact::STATUS_RESPONDED] ?? 0);

            if ($totalCount > 0 && $failedCount === $totalCount) {
                $segment->status = CampaignSegment::STATUS_FAILED;
            } else {
                $segment->status = CampaignSegment::STATUS_COMPLETED;
            }

            $segment->completed_at = now();
            $segment->save();

            // Check if all segments are done
            $campaign = $segment->campaign;
            $pendingSegments = $campaign->segments()
                ->whereIn('status', [CampaignSegment::STATUS_DRAFT, CampaignSegment::STATUS_IN_PROGRESS])
                ->exists();

            if (! $pendingSegments) {
                $allFailed = ! $campaign->segments()
                    ->where('status', CampaignSegment::STATUS_COMPLETED)
                    ->exists();

                $campaign->status = $allFailed ? Campaign::STATUS_FAILED : Campaign::STATUS_COMPLETED;
                $campaign->completed_at = now();
                $campaign->save();
            }
        });
    }
}
