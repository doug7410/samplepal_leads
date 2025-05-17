<?php

namespace App\Commands\Campaigns;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;

class SendCampaignCommand extends CampaignCommand
{
    /**
     * Execute the command to send the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        // Use the state pattern to update the status
        $result = $this->campaign->send();

        if ($result) {
            // Dispatch the campaign processing job
            ProcessCampaignJob::dispatch($this->campaign);
        }

        return $result;
    }
}
