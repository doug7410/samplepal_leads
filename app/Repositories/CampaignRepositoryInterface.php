<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Support\Collection;

interface CampaignRepositoryInterface extends RepositoryInterface
{
    /**
     * Get active campaigns that need processing
     */
    public function getActiveCampaigns(): Collection;

    /**
     * Get scheduled campaigns that are due to start
     */
    public function getDueCampaigns(): Collection;

    /**
     * Find campaigns by status
     */
    public function findByStatus(string $status): Collection;

    /**
     * Get campaign with contacts
     */
    public function findWithContacts(int $id): ?Campaign;

    /**
     * Add contacts to a campaign
     *
     * @return int Number of contacts added
     */
    public function addContacts(Campaign $campaign, array $contactIds): int;

    /**
     * Remove contacts from a campaign
     *
     * @return int Number of contacts removed
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int;

    /**
     * Get campaign statistics
     */
    public function getStatistics(Campaign $campaign): array;
}
