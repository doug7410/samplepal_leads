<?php

namespace App\Commands\Campaigns;

class RemoveContactsCommand extends CampaignCommand
{
    /**
     * Contact IDs to remove
     */
    protected array $contactIds;

    /**
     * Create a new command instance
     */
    public function __construct(\App\Models\Campaign $campaign, array $contactIds)
    {
        parent::__construct($campaign);
        $this->contactIds = $contactIds;
    }

    /**
     * Execute the command to remove contacts from the campaign
     *
     * @return int Number of contacts removed
     */
    public function execute(): int
    {
        return $this->campaign->removeContacts($this->contactIds);
    }
}
