<?php

namespace App\States;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class CompletedCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_COMPLETED;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'Completed';
    }

    /**
     * In completed state, most actions are not allowed except cloning the campaign
     */

    /**
     * Remove contacts is also disabled for completed campaigns
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        Log::info('Cannot remove contacts from completed campaign');

        return 0;
    }

    /**
     * Add contacts is also disabled for completed campaigns
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        Log::info('Cannot add contacts to completed campaign');

        return 0;
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return []; // No transitions allowed from completed state
    }
}
