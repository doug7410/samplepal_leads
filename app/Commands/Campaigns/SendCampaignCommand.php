<?php

namespace App\Commands\Campaigns;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Services\CampaignService;

class SendCampaignCommand extends CampaignCommand
{
    /**
     * Campaign service
     */
    protected CampaignService $campaignService;

    /**
     * Create a new SendCampaignCommand instance
     */
    public function __construct(Campaign $campaign)
    {
        parent::__construct($campaign);
        $this->campaignService = app(CampaignService::class);
    }

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
            if ($this->campaign->type === Campaign::TYPE_COMPANY) {
                $this->campaignService->processCompanyCampaign($this->campaign);
            } else {
                ProcessCampaignJob::dispatch($this->campaign);
            }
        }

        return $result;
    }
}
