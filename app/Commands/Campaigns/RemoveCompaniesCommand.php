<?php

namespace App\Commands\Campaigns;

use App\Models\Campaign;
use App\Services\CampaignService;

class RemoveCompaniesCommand extends CampaignCommand
{
    /**
     * Company IDs to remove
     */
    protected array $companyIds;

    /**
     * Campaign service
     */
    protected CampaignService $campaignService;

    /**
     * Create a new RemoveCompaniesCommand instance
     */
    public function __construct(Campaign $campaign, array $companyIds)
    {
        parent::__construct($campaign);
        $this->companyIds = $companyIds;
        $this->campaignService = app(CampaignService::class);
    }

    /**
     * Execute the command to remove companies from the campaign
     *
     * @return int Number of companies removed
     */
    public function execute(): int
    {
        return $this->campaignService->removeCompanies($this->campaign, $this->companyIds);
    }
}
