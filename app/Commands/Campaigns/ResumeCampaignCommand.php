<?php

namespace App\Commands\Campaigns;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;

class ResumeCampaignCommand extends CampaignCommand
{
    /**
     * Execute the command to resume the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        $result = $this->campaign->resume();

        // If successfully resumed and now in progress, dispatch the processing job
        if ($result && $this->campaign->status === Campaign::STATUS_IN_PROGRESS) {
            ProcessCampaignJob::dispatch($this->campaign);
        }

        return $result;
    }
}
