<?php

namespace App\Commands\Campaigns;

class AddContactsCommand extends CampaignCommand
{
    /**
     * Contact IDs to add
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
     * Execute the command to add contacts to the campaign
     *
     * @return int Number of contacts added
     */
    public function execute(): int
    {
        return $this->campaign->addContacts($this->contactIds);
    }
}
