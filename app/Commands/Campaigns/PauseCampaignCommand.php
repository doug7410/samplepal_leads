<?php

namespace App\Commands\Campaigns;

class PauseCampaignCommand extends CampaignCommand
{
    /**
     * Execute the command to pause the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        return $this->campaign->pause();
    }
}
