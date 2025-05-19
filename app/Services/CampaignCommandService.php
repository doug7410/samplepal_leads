<?php

namespace App\Services;

use App\Commands\Campaigns\AddCompaniesCommand;
use App\Commands\Campaigns\AddContactsCommand;
use App\Commands\Campaigns\PauseCampaignCommand;
use App\Commands\Campaigns\RemoveCompaniesCommand;
use App\Commands\Campaigns\RemoveContactsCommand;
use App\Commands\Campaigns\ResumeCampaignCommand;
use App\Commands\Campaigns\ScheduleCampaignCommand;
use App\Commands\Campaigns\SendCampaignCommand;
use App\Commands\Campaigns\StopCampaignCommand;
use App\Commands\CommandInvoker;
use App\Models\Campaign;

class CampaignCommandService
{
    /**
     * The command invoker
     */
    protected CommandInvoker $invoker;

    /**
     * Create a new CampaignCommandService instance
     */
    public function __construct(CommandInvoker $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * Send a campaign immediately
     *
     * @return bool Whether the operation was successful
     */
    public function send(Campaign $campaign): bool
    {
        $command = new SendCampaignCommand($campaign);

        return $this->invoker->execute($command);
    }

    /**
     * Schedule a campaign for future sending
     *
     * @return bool Whether the operation was successful
     */
    public function schedule(Campaign $campaign, array $data): bool
    {
        $command = new ScheduleCampaignCommand($campaign, $data);

        return $this->invoker->execute($command);
    }

    /**
     * Pause a campaign
     *
     * @return bool Whether the operation was successful
     */
    public function pause(Campaign $campaign): bool
    {
        $command = new PauseCampaignCommand($campaign);

        return $this->invoker->execute($command);
    }

    /**
     * Resume a campaign
     *
     * @return bool Whether the operation was successful
     */
    public function resume(Campaign $campaign): bool
    {
        $command = new ResumeCampaignCommand($campaign);

        return $this->invoker->execute($command);
    }

    /**
     * Stop a campaign
     *
     * @return bool Whether the operation was successful
     */
    public function stop(Campaign $campaign): bool
    {
        $command = new StopCampaignCommand($campaign);

        return $this->invoker->execute($command);
    }

    /**
     * Add contacts to a campaign
     *
     * @return int Number of contacts added
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        $command = new AddContactsCommand($campaign, $contactIds);

        return $this->invoker->execute($command);
    }

    /**
     * Remove contacts from a campaign
     *
     * @return int Number of contacts removed
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        $command = new RemoveContactsCommand($campaign, $contactIds);

        return $this->invoker->execute($command);
    }

    /**
     * Add companies to a campaign
     *
     * @return int Number of companies added
     */
    public function addCompanies(Campaign $campaign, array $companyIds): int
    {
        $command = new AddCompaniesCommand($campaign, $companyIds);

        return $this->invoker->execute($command);
    }

    /**
     * Remove companies from a campaign
     *
     * @return int Number of companies removed
     */
    public function removeCompanies(Campaign $campaign, array $companyIds): int
    {
        $command = new RemoveCompaniesCommand($campaign, $companyIds);

        return $this->invoker->execute($command);
    }
}
