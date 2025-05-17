<?php

namespace App\States;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class FailedCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_FAILED;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'Failed';
    }

    /**
     * In failed state, no actions are allowed except retrying by copying to a new campaign
     */

    /**
     * Remove contacts is also disabled for failed campaigns
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        Log::info('Cannot remove contacts from failed campaign');

        return 0;
    }

    /**
     * Add contacts is also disabled for failed campaigns
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        Log::info('Cannot add contacts to failed campaign');

        return 0;
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return []; // No transitions allowed from failed state
    }
}
