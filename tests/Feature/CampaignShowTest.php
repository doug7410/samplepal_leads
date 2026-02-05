<?php

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

it('loads the campaign show page when a campaign contact has a deleted contact', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $contact = Contact::factory()->create(['company_id' => $company->id]);
    $deletedContact = Contact::factory()->create(['company_id' => $company->id]);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $deletedContact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    // Delete the contact to simulate an orphaned campaign_contact
    $deletedContact->deleteQuietly();

    $response = $this->actingAs($user)->get(route('campaigns.show', $campaign));

    $response->assertSuccessful();

    $campaignContacts = $response->original->getData()['page']['props']['campaign']['campaign_contacts'];
    expect($campaignContacts)->toHaveCount(1);
    expect($campaignContacts[0]['contact_id'])->toBe($contact->id);
});
