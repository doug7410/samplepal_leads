<?php

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('soft deletes a company via the destroy endpoint', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this->actingAs($user)->delete(route('companies.destroy', $company));

    $response->assertRedirect();
    $this->assertSoftDeleted('companies', ['id' => $company->id]);
});

it('excludes contacts from deleted companies when adding to a new campaign', function () {
    $user = User::factory()->create();

    $activeCompany = Company::factory()->create();
    $deletedCompany = Company::factory()->create();
    $deletedCompany->delete();

    $activeContact = Contact::factory()->create([
        'company_id' => $activeCompany->id,
        'email' => 'active@example.com',
        'has_unsubscribed' => false,
    ]);

    $deletedCompanyContact = Contact::factory()->create([
        'company_id' => $deletedCompany->id,
        'email' => 'deleted@example.com',
        'has_unsubscribed' => false,
    ]);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $service = app(CampaignService::class);
    $count = $service->addContacts($campaign, [$activeContact->id, $deletedCompanyContact->id]);

    expect($count)->toBe(1);
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $activeContact->id,
    ]);
    $this->assertDatabaseMissing('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $deletedCompanyContact->id,
    ]);
});

it('excludes contacts from deleted companies when adding via filter', function () {
    $user = User::factory()->create();

    $activeCompany = Company::factory()->create();
    $deletedCompany = Company::factory()->create();
    $deletedCompany->delete();

    Contact::factory()->create([
        'company_id' => $activeCompany->id,
        'email' => 'active@example.com',
        'has_unsubscribed' => false,
    ]);

    Contact::factory()->create([
        'company_id' => $deletedCompany->id,
        'email' => 'deleted@example.com',
        'has_unsubscribed' => false,
    ]);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $service = app(CampaignService::class);
    $count = $service->addContactsFromFilter($campaign, []);

    expect($count)->toBe(1);
});

it('excludes contacts from deleted companies when adding to a sequence', function () {
    $user = User::factory()->create();

    $activeCompany = Company::factory()->create();
    $deletedCompany = Company::factory()->create();
    $deletedCompany->delete();

    $activeContact = Contact::factory()->create([
        'company_id' => $activeCompany->id,
        'email' => 'active@example.com',
        'has_unsubscribed' => false,
        'deal_status' => 'none',
    ]);

    $deletedCompanyContact = Contact::factory()->create([
        'company_id' => $deletedCompany->id,
        'email' => 'deleted@example.com',
        'has_unsubscribed' => false,
        'deal_status' => 'none',
    ]);

    $sequence = \App\Models\Sequence::create([
        'name' => 'Test Sequence',
        'user_id' => $user->id,
        'status' => 'draft',
    ]);
    $sequence->steps()->create([
        'step_order' => 0,
        'name' => 'Step 1',
        'subject' => 'Test',
        'content' => 'Test content',
        'delay_days' => 0,
    ]);

    $service = app(SequenceService::class);
    $count = $service->addContactsToSequence($sequence, [$activeContact->id, $deletedCompanyContact->id]);

    expect($count)->toBe(1);
    $this->assertDatabaseHas('sequence_contacts', [
        'sequence_id' => $sequence->id,
        'contact_id' => $activeContact->id,
    ]);
    $this->assertDatabaseMissing('sequence_contacts', [
        'sequence_id' => $sequence->id,
        'contact_id' => $deletedCompanyContact->id,
    ]);
});
