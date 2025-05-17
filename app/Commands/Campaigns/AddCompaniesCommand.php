<?php

namespace App\Commands\Campaigns;

use App\Models\Campaign;
use App\Services\CampaignService;

class AddCompaniesCommand extends CampaignCommand
{
    /**
     * Company IDs to add
     */
    protected array $companyIds;
    
    /**
     * Campaign service
     */
    protected CampaignService $campaignService;
    
    /**
     * Create a new AddCompaniesCommand instance
     */
    public function __construct(Campaign $campaign, array $companyIds)
    {
        parent::__construct($campaign);
        $this->companyIds = $companyIds;
        $this->campaignService = app(CampaignService::class);
    }
    
    /**
     * Execute the command to add companies to the campaign
     *
     * @return int Number of companies added
     */
    public function execute(): int
    {
        return $this->campaignService->addCompanies($this->campaign, $this->companyIds);
    }
}