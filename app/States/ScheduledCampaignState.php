<?php

namespace App\States;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class ScheduledCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_SCHEDULED;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'Scheduled';
    }

    /**
     * Schedule the campaign for a different time
     */
    public function schedule(Campaign $campaign, array $data): bool
    {
        // Validation
        if (empty($data['scheduled_at'])) {
            Log::error('Cannot reschedule campaign without a scheduled_at date');

            return false;
        }

        try {
            $campaign->scheduled_at = $data['scheduled_at'];
            $campaign->save();

            Log::info("Campaign #{$campaign->id} rescheduled for {$campaign->scheduled_at}");

            return true;
        } catch (\Exception $e) {
            Log::error('Error rescheduling campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send the campaign immediately (ignoring scheduled time)
     */
    public function send(Campaign $campaign): bool
    {
        try {
            // Check if there are any contacts before starting
            $contactCount = $campaign->campaignContacts()->count();
            if ($contactCount === 0) {
                Log::error('Cannot send campaign with no contacts');

                return false;
            }

            // Update status to in progress
            $campaign->status = Campaign::STATUS_IN_PROGRESS;
            $campaign->save();

            // Dispatch the job to process the campaign
            ProcessCampaignJob::dispatch($campaign);

            Log::info("Campaign #{$campaign->id} started immediately (was scheduled for {$campaign->scheduled_at})");

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Stop/cancel the campaign
     */
    public function stop(Campaign $campaign): bool
    {
        try {
            // Revert to draft state
            $campaign->status = Campaign::STATUS_DRAFT;
            $campaign->scheduled_at = null;
            $campaign->save();

            Log::info("Scheduled campaign #{$campaign->id} cancelled and reverted to draft");

            return true;
        } catch (\Exception $e) {
            Log::error('Error cancelling scheduled campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return [
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_IN_PROGRESS,
        ];
    }
}
