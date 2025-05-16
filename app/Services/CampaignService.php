<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\CampaignContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Create a new campaign.
     *
     * @param array $data
     * @return Campaign
     */
    public function createCampaign(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            return Campaign::create($data);
        });
    }
    
    /**
     * Update an existing campaign.
     *
     * @param Campaign $campaign
     * @param array $data
     * @return Campaign
     */
    public function updateCampaign(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $campaign->update($data);
            return $campaign;
        });
    }
    
    /**
     * Delete a campaign.
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function deleteCampaign(Campaign $campaign): bool
    {
        return DB::transaction(function () use ($campaign) {
            return $campaign->delete();
        });
    }
    
    /**
     * Add contacts to a campaign based on filter criteria.
     *
     * @param Campaign $campaign
     * @param array $filterCriteria
     * @return int Number of contacts added
     */
    public function addContactsFromFilter(Campaign $campaign, array $filterCriteria): int
    {
        return DB::transaction(function () use ($campaign, $filterCriteria) {
            // Start with query builder
            $query = Contact::query();
            
            // Apply filters
            if (!empty($filterCriteria['company_id'])) {
                $query->where('company_id', $filterCriteria['company_id']);
            }
            
            if (!empty($filterCriteria['relevance_min'])) {
                $query->where('relevance_score', '>=', $filterCriteria['relevance_min']);
            }
            
            if (!empty($filterCriteria['deal_status'])) {
                $query->whereIn('deal_status', (array) $filterCriteria['deal_status']);
            }
            
            // Make sure contacts have emails and aren't already in this campaign
            $query->whereNotNull('email')
                ->whereNotIn('id', function ($subquery) use ($campaign) {
                    $subquery->select('contact_id')
                        ->from('campaign_contacts')
                        ->where('campaign_id', $campaign->id);
                });
            
            // Get the contacts
            $contacts = $query->get();
            
            // Add them to the campaign
            $contactData = $contacts->map(function ($contact) use ($campaign) {
                return [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            
            if (!empty($contactData)) {
                CampaignContact::insert($contactData);
            }
            
            return count($contactData);
        });
    }
    
    /**
     * Add specific contacts to a campaign.
     *
     * @param Campaign $campaign
     * @param array $contactIds
     * @return int Number of contacts added
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        return DB::transaction(function () use ($campaign, $contactIds) {
            // Find contacts not already in the campaign
            $contacts = Contact::whereIn('id', $contactIds)
                ->whereNotNull('email')
                ->whereNotIn('id', function ($query) use ($campaign) {
                    $query->select('contact_id')
                        ->from('campaign_contacts')
                        ->where('campaign_id', $campaign->id);
                })
                ->get();
            
            // Add them to the campaign
            $contactData = $contacts->map(function ($contact) use ($campaign) {
                return [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            
            if (!empty($contactData)) {
                CampaignContact::insert($contactData);
            }
            
            return count($contactData);
        });
    }
    
    /**
     * Remove contacts from a campaign.
     *
     * @param Campaign $campaign
     * @param array $contactIds
     * @return int Number of contacts removed
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        return DB::transaction(function () use ($campaign, $contactIds) {
            return CampaignContact::where('campaign_id', $campaign->id)
                ->whereIn('contact_id', $contactIds)
                ->delete();
        });
    }
    
    /**
     * Update campaign status.
     *
     * @param Campaign $campaign
     * @param string $status
     * @return Campaign
     */
    public function updateStatus(Campaign $campaign, string $status): Campaign
    {
        return DB::transaction(function () use ($campaign, $status) {
            $campaign->status = $status;
            
            if ($status === 'completed') {
                $campaign->completed_at = now();
            }
            
            $campaign->save();
            return $campaign;
        });
    }
    
    /**
     * Get campaign statistics.
     *
     * @param Campaign $campaign
     * @return array
     */
    public function getStatistics(Campaign $campaign): array
    {
        $statusCounts = CampaignContact::where('campaign_id', $campaign->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Initialize all possible statuses with zero count
        $allStatuses = [
            'pending' => 0,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'responded' => 0,
            'bounced' => 0,
            'failed' => 0,
        ];
        
        // Merge with actual counts
        $statuses = array_merge($allStatuses, $statusCounts);
        
        // Calculate total and delivery rate
        $total = array_sum($statuses);
        $deliveryRate = $total > 0 ? round(($statuses['delivered'] / $total) * 100, 2) : 0;
        $openRate = $statuses['delivered'] > 0 ? round(($statuses['opened'] / $statuses['delivered']) * 100, 2) : 0;
        $clickRate = $statuses['opened'] > 0 ? round(($statuses['clicked'] / $statuses['opened']) * 100, 2) : 0;
        $responseRate = $statuses['delivered'] > 0 ? round(($statuses['responded'] / $statuses['delivered']) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'statuses' => $statuses,
            'rates' => [
                'delivery' => $deliveryRate,
                'open' => $openRate,
                'click' => $clickRate,
                'response' => $responseRate,
            ],
        ];
    }
}