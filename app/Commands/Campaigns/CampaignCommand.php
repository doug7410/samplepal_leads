<?php

namespace App\Commands\Campaigns;

use App\Commands\CommandInterface;
use App\Models\Campaign;

abstract class CampaignCommand implements CommandInterface
{
    /**
     * The campaign to operate on
     */
    protected Campaign $campaign;

    /**
     * Create a new command instance
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }
}
