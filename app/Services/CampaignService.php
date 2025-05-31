<?php

namespace App\Services;

use App\Helpers\RecipientsFormatter;
use App\Jobs\SendCompanyCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Create a new campaign.
     */
    public function createCampaign(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            return Campaign::create($data);
        });
    }

    /**
     * Update an existing campaign.
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
     * @return int Number of contacts added
     */
    public function addContactsFromFilter(Campaign $campaign, array $filterCriteria): int
    {
        return DB::transaction(function () use ($campaign, $filterCriteria) {
            // Start with query builder
            $query = Contact::query();

            // Apply filters
            if (! empty($filterCriteria['company_id'])) {
                $query->where('company_id', $filterCriteria['company_id']);
            }

            if (! empty($filterCriteria['relevance_min'])) {
                $query->where('relevance_score', '>=', $filterCriteria['relevance_min']);
            }

            if (! empty($filterCriteria['deal_status'])) {
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

            if (! empty($contactData)) {
                CampaignContact::insert($contactData);
            }

            return count($contactData);
        });
    }

    /**
     * Add specific contacts to a campaign.
     *
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

            if (! empty($contactData)) {
                CampaignContact::insert($contactData);
            }

            return count($contactData);
        });
    }

    /**
     * Remove contacts from a campaign.
     *
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
     * Add companies to a campaign.
     *
     * @return int Number of companies added
     */
    public function addCompanies(Campaign $campaign, array $companyIds): int
    {
        return DB::transaction(function () use ($campaign, $companyIds) {
            // Find companies not already in the campaign
            $companies = Company::whereIn('id', $companyIds)
                ->whereNotIn('id', function ($query) use ($campaign) {
                    $query->select('company_id')
                        ->from('campaign_companies')
                        ->where('campaign_id', $campaign->id);
                })
                ->get();

            // Add them to the campaign
            if ($companies->isNotEmpty()) {
                $campaign->companies()->attach($companies->pluck('id')->toArray());
            }

            return $companies->count();
        });
    }

    /**
     * Remove companies from a campaign.
     *
     * @return int Number of companies removed
     */
    public function removeCompanies(Campaign $campaign, array $companyIds): int
    {
        return DB::transaction(function () use ($campaign, $companyIds) {
            return $campaign->companies()->detach($companyIds);
        });
    }

    /**
     * Prepare all contacts from associated companies for processing in a company campaign.
     *
     * @return int Number of contacts added to the campaign
     */
    public function prepareCompanyContactsForProcessing(Campaign $campaign): int
    {
        return DB::transaction(function () use ($campaign) {
            // Make sure this is a company campaign
            if ($campaign->type !== Campaign::TYPE_COMPANY) {
                return 0;
            }

            $contactsAdded = 0;

            // Get all companies associated with this campaign
            $companies = $campaign->companies;

            foreach ($companies as $company) {
                // Get all contacts for this company that have valid emails
                $contacts = $company->contacts()
                    ->whereNotNull('email')
                    ->whereNotIn('id', function ($query) use ($campaign) {
                        $query->select('contact_id')
                            ->from('campaign_contacts')
                            ->where('campaign_id', $campaign->id);
                    })
                    ->get();

                // Add all company contacts to the campaign
                $contactData = $contacts->map(function ($contact) use ($campaign) {
                    return [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                if (! empty($contactData)) {
                    CampaignContact::insert($contactData);
                    $contactsAdded += count($contactData);
                }
            }

            return $contactsAdded;
        });
    }

    /**
     * Get a formatted list of recipients for a company.
     *
     * @return string Formatted recipient list (e.g., "Doug, Angela, and John")
     */
    public function getRecipientsListForCompany(Company $company): string
    {
        $contacts = $company->contacts()->whereNotNull('email')->get();

        return RecipientsFormatter::format($contacts);
    }

    /**
     * Get campaign statistics.
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
            'processing' => 0,
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'responded' => 0,
            'bounced' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'unsubscribed' => 0,
            'demo_scheduled' => 0,
        ];

        // Merge with actual counts
        $statuses = array_merge($allStatuses, $statusCounts);

        // Calculate total and rates
        $total = array_sum($statuses);

        // Delivered count includes all emails that were successfully delivered
        // (delivered, opened, clicked, responded all imply successful delivery)
        $deliveredCount = $statuses['delivered'] + $statuses['opened'] + $statuses['clicked'] + $statuses['responded'];

        $deliveryRate = $total > 0 ? round(($deliveredCount / $total) * 100, 2) : 0;
        $openRate = $deliveredCount > 0 ? round(($statuses['opened'] / $deliveredCount) * 100, 2) : 0;
        $clickRate = $deliveredCount > 0 ? round(($statuses['clicked'] / $deliveredCount) * 100, 2) : 0;
        $responseRate = $deliveredCount > 0 ? round(($statuses['responded'] / $deliveredCount) * 100, 2) : 0;

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

    /**
     * Process company campaigns by dispatching jobs for each company
     *
     * @param  Campaign  $campaign  The campaign to process
     * @return bool Whether the operation was successful
     */
    public function processCompanyCampaign(Campaign $campaign): bool
    {
        if ($campaign->type !== Campaign::TYPE_COMPANY) {
            return false;
        }

        $companies = $campaign->companies;

        if ($companies->isEmpty()) {
            return false;
        }

        foreach ($companies as $company) {
            SendCompanyCampaignEmailJob::dispatch($campaign, $company);
        }

        return true;
    }
}
