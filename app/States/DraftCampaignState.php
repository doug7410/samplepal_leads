<?php

namespace App\States;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class DraftCampaignState extends AbstractCampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string
    {
        return Campaign::STATUS_DRAFT;
    }

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string
    {
        return 'Draft';
    }

    /**
     * Schedule the campaign for future sending
     */
    public function schedule(Campaign $campaign, array $data): bool
    {
        // Validation
        if (empty($data['scheduled_at'])) {
            Log::error('Cannot schedule campaign without a scheduled_at date');

            return false;
        }

        try {
            $campaign->status = Campaign::STATUS_SCHEDULED;
            $campaign->scheduled_at = $data['scheduled_at'];
            $campaign->save();

            Log::info("Campaign #{$campaign->id} scheduled for {$campaign->scheduled_at}");

            return true;
        } catch (\Exception $e) {
            Log::error('Error scheduling campaign: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send the campaign immediately
     */
    public function send(Campaign $campaign): bool
    {
        try {
            // For company campaigns, check if there are companies
            if ($campaign->type === Campaign::TYPE_COMPANY) {
                $companyCount = $campaign->companies()->count();
                if ($companyCount === 0) {
                    Log::error('Cannot send company campaign with no companies');
                    return false;
                }
                
                // Update status to in progress
                $campaign->status = Campaign::STATUS_IN_PROGRESS;
                $campaign->save();
                
                Log::info("Company campaign #{$campaign->id} started with {$companyCount} companies");
                
                return true;
            } 
            // For contact campaigns, check if there are contacts
            else {
                $contactCount = $campaign->campaignContacts()->count();
                if ($contactCount === 0) {
                    Log::error('Cannot send contact campaign with no contacts');
                    return false;
                }
                
                // Update status to in progress
                $campaign->status = Campaign::STATUS_IN_PROGRESS;
                $campaign->save();
                
                Log::info("Contact campaign #{$campaign->id} started with {$contactCount} contacts");
                
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Error sending campaign: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Get allowable transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return [
            Campaign::STATUS_SCHEDULED,
            Campaign::STATUS_IN_PROGRESS,
        ];
    }
}
