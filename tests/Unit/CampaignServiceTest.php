<?php

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->campaignService = new CampaignService;
});

it('can create a campaign', function () {
    $user = User::factory()->create();

    $campaignData = [
        'name' => 'Test Campaign',
        'description' => 'A test campaign',
        'subject' => 'Test Subject',
        'content' => 'Test content for the email',
        'from_email' => 'test@example.com',
        'from_name' => 'Test Sender',
        'reply_to' => 'reply@example.com',
        'status' => Campaign::STATUS_DRAFT,
        'user_id' => $user->id,
        'filter_criteria' => ['manufacturer' => 'Acuity'],
    ];

    $campaign = $this->campaignService->createCampaign($campaignData);

    expect($campaign)->toBeInstanceOf(Campaign::class)
        ->and($campaign->name)->toBe('Test Campaign')
        ->and($campaign->subject)->toBe('Test Subject')
        ->and($campaign->status)->toBe(Campaign::STATUS_DRAFT);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'name' => 'Test Campaign',
    ]);
});

it('can update a campaign', function () {
    $campaign = Campaign::factory()->create([
        'name' => 'Original Campaign',
        'subject' => 'Original Subject',
    ]);

    $updatedData = [
        'name' => 'Updated Campaign',
        'subject' => 'Updated Subject',
    ];

    $updatedCampaign = $this->campaignService->updateCampaign($campaign, $updatedData);

    expect($updatedCampaign->name)->toBe('Updated Campaign')
        ->and($updatedCampaign->subject)->toBe('Updated Subject');

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'name' => 'Updated Campaign',
        'subject' => 'Updated Subject',
    ]);
});

it('can delete a campaign', function () {
    $campaign = Campaign::factory()->create();

    $result = $this->campaignService->deleteCampaign($campaign);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('campaigns', [
        'id' => $campaign->id,
    ]);
});

it('can add contacts to a campaign based on filter criteria', function () {
    $campaign = Campaign::factory()->create();

    // Create companies with different manufacturers
    $acuityCompany = Company::factory()->create(['manufacturer' => 'Acuity']);
    $cooperCompany = Company::factory()->create(['manufacturer' => 'Cooper']);

    // Create contacts for each company with different relevance scores
    Contact::factory()->create([
        'company_id' => $acuityCompany->id,
        'relevance_score' => 8,
        'deal_status' => 'contacted',
    ]);

    Contact::factory()->create([
        'company_id' => $acuityCompany->id,
        'relevance_score' => 3,
        'deal_status' => 'none',
    ]);

    Contact::factory()->create([
        'company_id' => $cooperCompany->id,
        'relevance_score' => 9,
        'deal_status' => 'responded',
    ]);

    // Filter for Acuity contacts with relevance score >= 5
    $filterCriteria = [
        'company_id' => $acuityCompany->id,
        'relevance_min' => 5,
        'deal_status' => ['contacted', 'responded'],
    ];

    $addedCount = $this->campaignService->addContactsFromFilter($campaign, $filterCriteria);

    expect($addedCount)->toBe(1);

    $campaign->refresh();
    expect($campaign->contacts)->toHaveCount(1)
        ->and($campaign->contacts->first()->relevance_score)->toBe(8)
        ->and($campaign->contacts->first()->company_id)->toBe($acuityCompany->id);

    // Check campaign_contacts pivot record
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $campaign->contacts->first()->id,
        'status' => 'pending',
    ]);
});

it('can add specific contacts to a campaign', function () {
    $campaign = Campaign::factory()->create();
    $contact1 = Contact::factory()->create();
    $contact2 = Contact::factory()->create();
    $contact3 = Contact::factory()->create();

    $contactIds = [$contact1->id, $contact3->id];

    $addedCount = $this->campaignService->addContacts($campaign, $contactIds);

    expect($addedCount)->toBe(2);

    $campaign->refresh();
    expect($campaign->contacts)->toHaveCount(2)
        ->and($campaign->contacts->pluck('id')->toArray())->toContain($contact1->id, $contact3->id)
        ->and($campaign->contacts->pluck('id')->toArray())->not->toContain($contact2->id);

    // Check campaign_contacts pivot records
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact1->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact3->id,
        'status' => 'pending',
    ]);
});

it('can remove contacts from a campaign', function () {
    $campaign = Campaign::factory()->create();
    $contact1 = Contact::factory()->create();
    $contact2 = Contact::factory()->create();
    $contact3 = Contact::factory()->create();

    // Add all contacts
    $this->campaignService->addContacts($campaign, [$contact1->id, $contact2->id, $contact3->id]);

    $campaign->refresh();
    expect($campaign->contacts)->toHaveCount(3);

    // Remove two contacts
    $removedCount = $this->campaignService->removeContacts($campaign, [$contact1->id, $contact3->id]);

    expect($removedCount)->toBe(2);

    $campaign->refresh();
    expect($campaign->contacts)->toHaveCount(1)
        ->and($campaign->contacts->first()->id)->toBe($contact2->id);

    // Check campaign_contacts pivot records
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact1->id,
    ]);

    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact2->id,
    ]);

    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact3->id,
    ]);
});

it('can update campaign status', function () {
    $campaign = Campaign::factory()->create([
        'status' => Campaign::STATUS_DRAFT,
        'completed_at' => null,
    ]);

    $updatedCampaign = $this->campaignService->updateStatus($campaign, Campaign::STATUS_IN_PROGRESS);

    expect($updatedCampaign->status)->toBe(Campaign::STATUS_IN_PROGRESS)
        ->and($updatedCampaign->completed_at)->toBeNull();

    $updatedCampaign = $this->campaignService->updateStatus($campaign, Campaign::STATUS_COMPLETED);

    expect($updatedCampaign->status)->toBe(Campaign::STATUS_COMPLETED)
        ->and($updatedCampaign->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('can calculate campaign statistics', function () {
    $campaign = Campaign::factory()->create();

    // Create 10 contacts
    $contacts = Contact::factory()->count(10)->create();

    // Add all contacts to the campaign with different statuses
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[0]->id, 'status' => 'pending']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[1]->id, 'status' => 'processing']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[2]->id, 'status' => 'sent']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[3]->id, 'status' => 'delivered']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[4]->id, 'status' => 'delivered']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[5]->id, 'status' => 'opened']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[6]->id, 'status' => 'opened']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[7]->id, 'status' => 'clicked']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[8]->id, 'status' => 'responded']);
    CampaignContact::create(['campaign_id' => $campaign->id, 'contact_id' => $contacts[9]->id, 'status' => 'failed']);

    $stats = $this->campaignService->getStatistics($campaign);

    expect($stats)->toBeArray()
        ->and($stats['total'])->toBe(10)
        ->and($stats['statuses'])->toBeArray()
        ->and($stats['statuses']['pending'])->toBe(1)
        ->and($stats['statuses']['processing'])->toBe(1)
        ->and($stats['statuses']['sent'])->toBe(1)
        ->and($stats['statuses']['delivered'])->toBe(2)
        ->and($stats['statuses']['opened'])->toBe(2)
        ->and($stats['statuses']['clicked'])->toBe(1)
        ->and($stats['statuses']['responded'])->toBe(1)
        ->and($stats['statuses']['failed'])->toBe(1)
        ->and($stats['rates'])->toBeArray()
        ->and($stats['rates']['delivery'])->toBe(60.0) // 6 delivered (2 delivered + 2 opened + 1 clicked + 1 responded) out of 10 total
        ->and($stats['rates']['open'])->toBe(33.33) // 2 opened out of 6 delivered
        ->and($stats['rates']['click'])->toBe(16.67) // 1 clicked out of 6 delivered
        ->and($stats['rates']['response'])->toBe(16.67); // 1 responded out of 6 delivered
});
