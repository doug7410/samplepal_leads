<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\CampaignContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentCampaignRepository extends EloquentRepository implements CampaignRepositoryInterface
{
    /**
     * EloquentCampaignRepository constructor.
     */
    public function __construct(Campaign $model)
    {
        parent::__construct($model);
    }

    /**
     * Get active campaigns that need processing
     */
    public function getActiveCampaigns(): Collection
    {
        return $this->model->where('status', Campaign::STATUS_IN_PROGRESS)->get();
    }

    /**
     * Get scheduled campaigns that are due to start
     */
    public function getDueCampaigns(): Collection
    {
        return $this->model
            ->where('status', Campaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    /**
     * Find campaigns by status
     */
    public function findByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get campaign with contacts
     */
    public function findWithContacts(int $id): ?Campaign
    {
        return $this->model->with('contacts')->find($id);
    }

    /**
     * Add contacts to a campaign
     *
     * @return int Number of contacts added
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        return $campaign->addContacts($contactIds);
    }

    /**
     * Remove contacts from a campaign
     *
     * @return int Number of contacts removed
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        return $campaign->removeContacts($contactIds);
    }

    /**
     * Get campaign statistics
     */
    public function getStatistics(Campaign $campaign): array
    {
        // Get counts for each status
        $statusCounts = CampaignContact::where('campaign_id', $campaign->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Calculate total emails
        $totalEmails = array_sum($statusCounts);
        if ($totalEmails === 0) {
            return [
                'total' => 0,
                'status_distribution' => [],
                'delivery_rate' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
                'response_rate' => 0,
            ];
        }

        // Calculate rates
        $sentCount = ($statusCounts[CampaignContact::STATUS_SENT] ?? 0) +
                     ($statusCounts[CampaignContact::STATUS_DELIVERED] ?? 0) +
                     ($statusCounts[CampaignContact::STATUS_OPENED] ?? 0) +
                     ($statusCounts[CampaignContact::STATUS_CLICKED] ?? 0) +
                     ($statusCounts[CampaignContact::STATUS_RESPONDED] ?? 0);

        $openedCount = ($statusCounts[CampaignContact::STATUS_OPENED] ?? 0) +
                       ($statusCounts[CampaignContact::STATUS_CLICKED] ?? 0) +
                       ($statusCounts[CampaignContact::STATUS_RESPONDED] ?? 0);

        $clickedCount = ($statusCounts[CampaignContact::STATUS_CLICKED] ?? 0) +
                        ($statusCounts[CampaignContact::STATUS_RESPONDED] ?? 0);

        $respondedCount = $statusCounts[CampaignContact::STATUS_RESPONDED] ?? 0;

        $deliveryRate = $sentCount > 0 ? ($sentCount / $totalEmails) * 100 : 0;
        $openRate = $sentCount > 0 ? ($openedCount / $sentCount) * 100 : 0;
        $clickRate = $openedCount > 0 ? ($clickedCount / $openedCount) * 100 : 0;
        $responseRate = $sentCount > 0 ? ($respondedCount / $sentCount) * 100 : 0;

        return [
            'total' => $totalEmails,
            'status_distribution' => $statusCounts,
            'delivery_rate' => round($deliveryRate, 1),
            'open_rate' => round($openRate, 1),
            'click_rate' => round($clickRate, 1),
            'response_rate' => round($responseRate, 1),
        ];
    }
}
