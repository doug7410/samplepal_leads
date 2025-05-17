<?php

namespace App\States;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class PausedCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_PAUSED;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'Paused';
    }

    /**
     * Resume the campaign
     */
    public function resume(Campaign $campaign): bool
    {
        try {
            $campaign->status = Campaign::STATUS_IN_PROGRESS;
            $campaign->save();

            Log::info("Campaign #{$campaign->id} resumed");

            return true;
        } catch (\Exception $e) {
            Log::error('Error resuming campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Stop/cancel the campaign
     */
    public function stop(Campaign $campaign): bool
    {
        // Create an instance of InProgressCampaignState to reuse its stop implementation
        $inProgressState = new InProgressCampaignState;

        return $inProgressState->stop($campaign);
    }

    /**
     * Add contacts to the campaign
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        return parent::addContacts($campaign, $contactIds);
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return [
            Campaign::STATUS_IN_PROGRESS,
            Campaign::STATUS_COMPLETED,
        ];
    }
}
