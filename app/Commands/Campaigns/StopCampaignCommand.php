<?php

namespace App\Commands\Campaigns;

use App\Models\CampaignContact;

class StopCampaignCommand extends CampaignCommand
{
    /**
     * Execute the command to stop the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        // Mark pending, processing, or failed contacts as cancelled
        foreach ($this->campaign->campaignContacts()
            ->whereIn('status', [
                CampaignContact::STATUS_PENDING,
                CampaignContact::STATUS_PROCESSING,
                CampaignContact::STATUS_FAILED,
            ])->get() as $contact) {
            
            $contact->status = 'cancelled'; // Use string directly instead of constant for now
            $contact->failed_at = null;
            $contact->failure_reason = null;
            $contact->save();
        }

        // Stop the campaign via the state pattern
        return $this->campaign->stop();
    }
}
