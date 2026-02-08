<?php

namespace App\States;

use App\Models\Campaign;
use App\Models\CampaignContact;
use Illuminate\Support\Facades\Log;

class InProgressCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_IN_PROGRESS;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'In Progress';
    }

    /**
     * Pause the campaign
     */
    public function pause(Campaign $campaign): bool
    {
        try {
            $campaign->status = Campaign::STATUS_PAUSED;
            $campaign->save();

            Log::info("Campaign #{$campaign->id} paused");

            return true;
        } catch (\Exception $e) {
            Log::error('Error pausing campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Stop/cancel the campaign
     */
    public function stop(Campaign $campaign): bool
    {
        try {
            // Get count of emails that were already sent
            $sentCount = $campaign->campaignContacts()
                ->whereIn('status', [
                    CampaignContact::STATUS_SENT,
                    CampaignContact::STATUS_DELIVERED,
                    CampaignContact::STATUS_OPENED,
                    CampaignContact::STATUS_CLICKED,
                    CampaignContact::STATUS_RESPONDED,
                ])
                ->count();

            // Mark all pending emails as cancelled
            $campaign->campaignContacts()
                ->where('status', CampaignContact::STATUS_PENDING)
                ->update(['status' => CampaignContact::STATUS_CANCELLED]);

            // Mark the campaign as completed (partial)
            $campaign->status = Campaign::STATUS_COMPLETED;
            $campaign->completed_at = now();
            $campaign->save();

            Log::info("Campaign #{$campaign->id} stopped. {$sentCount} emails were already sent.");

            return true;
        } catch (\Exception $e) {
            Log::error('Error stopping campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Add contacts to the campaign
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        // In progress campaigns should only add contacts if they haven't finished processing
        $pendingCount = $campaign->campaignContacts()
            ->where('status', CampaignContact::STATUS_PENDING)
            ->count();

        if ($pendingCount === 0) {
            Log::info("Cannot add contacts to campaign #{$campaign->id} because it has already processed all contacts");

            return 0;
        }

        // If there are still pending contacts, we can add more
        return parent::addContacts($campaign, $contactIds);
    }

    /**
     * Check if the campaign can be processed by job processor.
     * Only in_progress campaigns can be processed.
     */
    public function canProcess(): bool
    {
        return true;
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return [
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_COMPLETED,
            Campaign::STATUS_FAILED,
        ];
    }
}
