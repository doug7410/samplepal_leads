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
        // Reset pending, processing, or failed contacts to pending state
        $this->campaign->campaignContacts()
            ->whereIn('status', [
                CampaignContact::STATUS_PENDING,
                CampaignContact::STATUS_PROCESSING,
                CampaignContact::STATUS_FAILED,
            ])
            ->update([
                'status' => CampaignContact::STATUS_PENDING,
                'failed_at' => null,
                'failure_reason' => null,
            ]);

        // Stop the campaign via the state pattern
        return $this->campaign->stop();
    }
}
