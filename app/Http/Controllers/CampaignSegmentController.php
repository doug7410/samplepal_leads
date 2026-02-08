<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignSegmentsRequest;
use App\Http\Requests\UpdateCampaignSegmentRequest;
use App\Models\Campaign;
use App\Models\CampaignSegment;
use App\Services\CampaignSegmentService;
use Illuminate\Http\RedirectResponse;

class CampaignSegmentController extends Controller
{
    public function __construct(protected CampaignSegmentService $segmentService) {}

    public function store(StoreCampaignSegmentsRequest $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be split into segments.');
        }

        if ($campaign->campaignContacts()->count() === 0) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cannot create segments for a campaign with no contacts.');
        }

        $this->segmentService->createSegments($campaign, $request->validated()['number_of_segments']);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign split into segments successfully.');
    }

    public function update(UpdateCampaignSegmentRequest $request, Campaign $campaign, CampaignSegment $segment): RedirectResponse
    {
        if ($segment->status !== CampaignSegment::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft segments can be edited.');
        }

        $this->segmentService->updateSegment($segment, $request->validated());

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Segment updated successfully.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $result = $this->segmentService->deleteSegments($campaign);

        if (! $result) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cannot remove segments that have already been sent.');
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Segments removed successfully.');
    }

    public function send(Campaign $campaign, CampaignSegment $segment): RedirectResponse
    {
        if ($segment->status !== CampaignSegment::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft segments can be sent.');
        }

        if (! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_IN_PROGRESS])) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Campaign is not in a sendable state.');
        }

        $this->segmentService->sendSegment($segment);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "Segment \"{$segment->name}\" is being sent.");
    }
}
