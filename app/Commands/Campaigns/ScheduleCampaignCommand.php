<?php

namespace App\Commands\Campaigns;

class ScheduleCampaignCommand extends CampaignCommand
{
    /**
     * The scheduling data
     */
    protected array $data;

    /**
     * Create a new command instance
     *
     * @param  array  $data  Scheduling data including 'scheduled_at'
     */
    public function __construct(\App\Models\Campaign $campaign, array $data)
    {
        parent::__construct($campaign);
        $this->data = $data;
    }

    /**
     * Execute the command to schedule the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        return $this->campaign->schedule($this->data);
    }
}
