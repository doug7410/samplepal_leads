<?php

namespace Tests\Unit\Models;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_campaign_type_contact_by_default()
    {
        $campaign = new Campaign;

        $this->assertEquals('contact', $campaign->type);
    }

    /** @test */
    public function it_can_have_campaign_type_company()
    {
        $campaign = new Campaign;
        $campaign->type = 'company';

        $this->assertEquals('company', $campaign->type);
    }

    /** @test */
    public function it_stores_campaign_type_in_database()
    {
        $user = User::factory()->create();

        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'type' => 'company',
        ]);
    }

    /** @test */
    public function it_can_get_company_ids_for_company_campaigns()
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id]);
        $company2 = Company::factory()->create(['user_id' => $user->id]);

        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $user->id,
            ]);

        // Associate companies with campaign
        $campaign->companies()->attach([$company1->id, $company2->id]);

        $this->assertCount(2, $campaign->companies);
        $this->assertTrue($campaign->companies->contains($company1));
        $this->assertTrue($campaign->companies->contains($company2));
    }

    /** @test */
    public function it_supports_both_contact_and_company_types_with_proper_relationships()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $contact1 = Contact::factory()->create(['user_id' => $user->id]);
        $contact2 = Contact::factory()->create(['user_id' => $user->id]);

        // Create a contact-type campaign
        $contactCampaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create a company-type campaign
        $companyCampaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $user->id,
            ]);

        // Associate contacts with contact campaign
        $contactCampaign->contacts()->attach([$contact1->id, $contact2->id]);

        // Associate company with company campaign
        $companyCampaign->companies()->attach([$company->id]);

        // Assertions
        $this->assertEquals('contact', $contactCampaign->type);
        $this->assertEquals('company', $companyCampaign->type);

        $this->assertCount(2, $contactCampaign->contacts);
        $this->assertCount(1, $companyCampaign->companies);
    }
}
