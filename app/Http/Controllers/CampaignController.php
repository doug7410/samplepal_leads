<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Services\CampaignCommandService;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    protected CampaignCommandService $commandService;

    /**
     * Constructor
     */
    public function __construct(CampaignService $campaignService, CampaignCommandService $commandService)
    {
        $this->campaignService = $campaignService;
        $this->commandService = $commandService;
    }

    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request): Response
    {
        $campaigns = Campaign::with('user')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): Response
    {
        $companies = \App\Models\Company::orderBy('company_name')->get();
        $contacts = \App\Models\Contact::with('company')->get();

        return Inertia::render('campaigns/create', [
            'companies' => $companies,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'from_email' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email|max:255',
            'filter_criteria' => 'nullable|array',
            'contact_ids' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = Campaign::STATUS_DRAFT;

        $campaign = $this->campaignService->createCampaign($validated);

        // Add contacts from specific IDs if provided
        if (isset($validated['contact_ids']) && ! empty($validated['contact_ids'])) {
            $this->campaignService->addContacts($campaign, $validated['contact_ids']);
        }
        // Or add contacts from filter criteria
        elseif (isset($validated['filter_criteria']) && ! empty($validated['filter_criteria'])) {
            $this->campaignService->addContactsFromFilter($campaign, $validated['filter_criteria']);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): Response
    {
        $campaign->load(['campaignContacts.contact']);

        $statistics = $this->campaignService->getStatistics($campaign);

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(Campaign $campaign): Response
    {
        // Load the campaign with its contacts
        $campaign->load('campaignContacts');

        $companies = \App\Models\Company::orderBy('company_name')->get();
        $contacts = \App\Models\Contact::with('company')->get();

        // Get contacts associated with this campaign
        $contactIds = $campaign->campaignContacts->pluck('contact_id')->toArray();
        $selectedContacts = Contact::whereIn('id', $contactIds)->get();

        return Inertia::render('campaigns/edit', [
            'campaign' => $campaign,
            'companies' => $companies,
            'contacts' => $contacts,
            'selectedContacts' => $selectedContacts,
        ]);
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        // Only allow editing of draft campaigns
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'from_email' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email|max:255',
            'filter_criteria' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $this->campaignService->updateCampaign($campaign, $validated);

        // Update campaign contacts if provided
        if (isset($validated['contact_ids'])) {
            // Remove all existing contacts
            $campaign->campaignContacts()->delete();

            // Add new contacts
            if (! empty($validated['contact_ids'])) {
                $this->campaignService->addContacts($campaign, $validated['contact_ids']);
            }
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified campaign from storage.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        // Only allow deletion of draft campaigns
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be deleted.');
        }

        $this->campaignService->deleteCampaign($campaign);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Add contacts to a campaign.
     */
    public function addContacts(Request $request, Campaign $campaign): RedirectResponse
    {
        // Only allow adding contacts to draft campaigns
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be modified.');
        }

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $count = $this->commandService->addContacts($campaign, $validated['contact_ids']);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "{$count} contacts added to campaign.");
    }

    /**
     * Remove contacts from a campaign.
     */
    public function removeContacts(Request $request, Campaign $campaign): RedirectResponse
    {
        // Only allow removing contacts from draft campaigns
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be modified.');
        }

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $count = $this->commandService->removeContacts($campaign, $validated['contact_ids']);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "{$count} contacts removed from campaign.");
    }

    /**
     * Schedule a campaign for sending.
     */
    public function schedule(Request $request, Campaign $campaign): RedirectResponse
    {
        // Only allow scheduling draft campaigns
        if ($campaign->status !== Campaign::STATUS_DRAFT) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft campaigns can be scheduled.');
        }

        // Check if the campaign has contacts
        if ($campaign->campaignContacts()->count() === 0) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cannot schedule a campaign with no contacts. Please add contacts first.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $success = $this->commandService->schedule($campaign, [
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with($success ? 'success' : 'error',
                $success ? 'Campaign scheduled successfully.' : 'Failed to schedule campaign.');
    }

    /**
     * Pause a campaign.
     */
    public function pause(Campaign $campaign): RedirectResponse
    {
        // Only allow pausing in-progress or scheduled campaigns
        if (! in_array($campaign->status, [Campaign::STATUS_IN_PROGRESS, Campaign::STATUS_SCHEDULED])) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only in-progress or scheduled campaigns can be paused.');
        }

        $success = $this->commandService->pause($campaign);

        return redirect()->route('campaigns.show', $campaign)
            ->with($success ? 'success' : 'error',
                $success ? 'Campaign paused successfully.' : 'Failed to pause campaign.');
    }

    /**
     * Resume a campaign.
     */
    public function resume(Campaign $campaign): RedirectResponse
    {
        // Only allow resuming paused campaigns
        if ($campaign->status !== Campaign::STATUS_PAUSED) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only paused campaigns can be resumed.');
        }

        $success = $this->commandService->resume($campaign);

        return redirect()->route('campaigns.show', $campaign)
            ->with($success ? 'success' : 'error',
                $success ? 'Campaign resumed successfully.' : 'Failed to resume campaign.');
    }

    /**
     * Stop a campaign and return it to draft status.
     */
    public function stop(Campaign $campaign): RedirectResponse
    {
        // Only allow stopping campaigns that are in progress, scheduled, paused, or failed
        if (! in_array($campaign->status, [
            Campaign::STATUS_IN_PROGRESS,
            Campaign::STATUS_SCHEDULED,
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_FAILED,
        ])) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'This campaign cannot be stopped.');
        }

        $success = $this->commandService->stop($campaign);

        return redirect()->route('campaigns.show', $campaign)
            ->with($success ? 'success' : 'error',
                $success ? 'Campaign stopped and reset to draft successfully.' : 'Failed to stop campaign.');
    }

    /**
     * Send a campaign immediately.
     */
    public function send(Campaign $campaign): RedirectResponse
    {
        // Only allow sending draft or scheduled campaigns
        if (! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED])) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Only draft or scheduled campaigns can be sent immediately.');
        }

        // Check if the campaign has contacts
        if ($campaign->campaignContacts()->count() === 0) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cannot send campaign with no contacts.');
        }

        $success = $this->commandService->send($campaign);

        return redirect()->route('campaigns.show', $campaign)
            ->with($success ? 'success' : 'error',
                $success ? 'Campaign is being sent.' : 'Failed to send campaign.');
    }
}
