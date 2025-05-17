<?php

namespace App\States;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class CampaignStateFactory
{
    /**
     * Create a state object for a campaign based on its status
     *
     * @param  Campaign|string  $statusOrCampaign  Campaign object or a status string
     */
    public static function createState($statusOrCampaign): CampaignState
    {
        // If passed a campaign object, extract the status
        $status = $statusOrCampaign instanceof Campaign
            ? $statusOrCampaign->status
            : $statusOrCampaign;

        return match ($status) {
            Campaign::STATUS_DRAFT => new DraftCampaignState,
            Campaign::STATUS_SCHEDULED => new ScheduledCampaignState,
            Campaign::STATUS_IN_PROGRESS => new InProgressCampaignState,
            Campaign::STATUS_PAUSED => new PausedCampaignState,
            Campaign::STATUS_COMPLETED => new CompletedCampaignState,
            Campaign::STATUS_FAILED => new FailedCampaignState,
            default => self::handleInvalidState($status),
        };
    }

    /**
     * Handle the case when an invalid status is provided
     */
    private static function handleInvalidState(string $status): CampaignState
    {
        Log::warning("Invalid campaign status '{$status}', defaulting to draft state");

        return new DraftCampaignState;
    }

    /**
     * Get all possible campaign states
     *
     * @return array Array of CampaignState objects
     */
    public static function getAllStates(): array
    {
        return [
            new DraftCampaignState,
            new ScheduledCampaignState,
            new InProgressCampaignState,
            new PausedCampaignState,
            new CompletedCampaignState,
            new FailedCampaignState,
        ];
    }
}
