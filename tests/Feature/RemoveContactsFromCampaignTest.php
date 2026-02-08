<?php

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->contacts = Contact::factory()->count(3)->create(['company_id' => $this->company->id]);
});

function createCampaignWithContacts(User $user, $contacts, string $status = Campaign::STATUS_DRAFT, array $contactStatuses = []): Campaign
{
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'status' => $status,
        'type' => Campaign::TYPE_CONTACT,
    ]);

    foreach ($contacts as $i => $contact) {
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => $contactStatuses[$i] ?? CampaignContact::STATUS_PENDING,
        ]);
    }

    return $campaign;
}

it('removes pending contacts from a draft campaign', function () {
    $campaign = createCampaignWithContacts($this->user, $this->contacts);

    $response = $this->actingAs($this->user)->post(
        route('campaigns.remove-contacts', $campaign),
        ['contact_ids' => [$this->contacts[0]->id]]
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[0]->id,
    ]);
    expect($campaign->campaignContacts()->count())->toBe(2);
});

it('removes pending contacts from an in-progress campaign', function () {
    $campaign = createCampaignWithContacts(
        $this->user,
        $this->contacts,
        Campaign::STATUS_IN_PROGRESS,
        [CampaignContact::STATUS_SENT, CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PENDING]
    );

    $response = $this->actingAs($this->user)->post(
        route('campaigns.remove-contacts', $campaign),
        ['contact_ids' => [$this->contacts[1]->id, $this->contacts[2]->id]]
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[1]->id,
    ]);
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[2]->id,
    ]);
    expect($campaign->campaignContacts()->count())->toBe(1);
});

it('does not remove sent contacts from an in-progress campaign', function () {
    $campaign = createCampaignWithContacts(
        $this->user,
        $this->contacts,
        Campaign::STATUS_IN_PROGRESS,
        [CampaignContact::STATUS_SENT, CampaignContact::STATUS_DELIVERED, CampaignContact::STATUS_PENDING]
    );

    $response = $this->actingAs($this->user)->post(
        route('campaigns.remove-contacts', $campaign),
        ['contact_ids' => [$this->contacts[0]->id, $this->contacts[1]->id, $this->contacts[2]->id]]
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[0]->id,
        'status' => CampaignContact::STATUS_SENT,
    ]);
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[1]->id,
        'status' => CampaignContact::STATUS_DELIVERED,
    ]);
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[2]->id,
    ]);
});

it('removes pending contacts from a paused campaign', function () {
    $campaign = createCampaignWithContacts(
        $this->user,
        $this->contacts,
        Campaign::STATUS_PAUSED,
        [CampaignContact::STATUS_SENT, CampaignContact::STATUS_PENDING, CampaignContact::STATUS_PENDING]
    );

    $response = $this->actingAs($this->user)->post(
        route('campaigns.remove-contacts', $campaign),
        ['contact_ids' => [$this->contacts[1]->id]]
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $this->contacts[1]->id,
    ]);
    expect($campaign->campaignContacts()->count())->toBe(2);
});

it('rejects removing contacts from a company-type campaign', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'status' => Campaign::STATUS_DRAFT,
        'type' => Campaign::TYPE_COMPANY,
    ]);

    $response = $this->actingAs($this->user)->post(
        route('campaigns.remove-contacts', $campaign),
        ['contact_ids' => [$this->contacts[0]->id]]
    );

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
