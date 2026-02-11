<?php

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

it('returns only assigned contacts as selectedContacts when no filters are active', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $assignedContacts = Contact::factory()->count(3)->create(['company_id' => $company->id]);
    $unassignedContacts = Contact::factory()->count(5)->create(['company_id' => $company->id]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => Campaign::TYPE_CONTACT,
        'filter_criteria' => [
            'company_id' => null,
            'relevance_min' => null,
            'exclude_deal_status' => [],
            'job_title' => null,
        ],
    ]);

    foreach ($assignedContacts as $contact) {
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);
    }

    $response = $this->actingAs($user)->get(route('campaigns.edit', $campaign));

    $response->assertSuccessful();

    $props = $response->original->getData()['page']['props'];
    $selectedContactIds = collect($props['selectedContacts'])->pluck('id')->sort()->values()->toArray();
    $expectedIds = $assignedContacts->pluck('id')->sort()->values()->toArray();

    expect($selectedContactIds)->toBe($expectedIds);
    expect($selectedContactIds)->toHaveCount(3);
});

it('does not include unassigned contacts in selectedContacts', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $assignedContact = Contact::factory()->create(['company_id' => $company->id]);
    $unassignedContact = Contact::factory()->create(['company_id' => $company->id]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => Campaign::TYPE_CONTACT,
        'filter_criteria' => [
            'company_id' => null,
            'relevance_min' => null,
            'exclude_deal_status' => [],
            'job_title' => null,
        ],
    ]);

    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $assignedContact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->get(route('campaigns.edit', $campaign));

    $response->assertSuccessful();

    $props = $response->original->getData()['page']['props'];
    $selectedContactIds = collect($props['selectedContacts'])->pluck('id')->toArray();

    expect($selectedContactIds)->toContain($assignedContact->id);
    expect($selectedContactIds)->not->toContain($unassignedContact->id);
});

it('preserves assigned contacts after updating campaign without changing contacts', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $assignedContacts = Contact::factory()->count(3)->create(['company_id' => $company->id]);
    Contact::factory()->count(5)->create(['company_id' => $company->id]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => Campaign::TYPE_CONTACT,
        'filter_criteria' => [
            'company_id' => null,
            'relevance_min' => null,
            'exclude_deal_status' => [],
            'job_title' => null,
        ],
    ]);

    foreach ($assignedContacts as $contact) {
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);
    }

    $response = $this->actingAs($user)->put(route('campaigns.update', $campaign), [
        'name' => 'Updated Name',
        'description' => $campaign->description,
        'subject' => $campaign->subject,
        'content' => $campaign->content,
        'from_email' => $campaign->from_email,
        'from_name' => $campaign->from_name,
        'type' => 'contact',
        'contact_ids' => $assignedContacts->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();

    $campaignContactIds = CampaignContact::where('campaign_id', $campaign->id)
        ->pluck('contact_id')
        ->sort()
        ->values()
        ->toArray();

    expect($campaignContactIds)->toBe($assignedContacts->pluck('id')->sort()->values()->toArray());
    expect($campaignContactIds)->toHaveCount(3);
});
