<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    private CampaignService $campaignService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignService = new CampaignService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_company_type_campaign()
    {
        $data = [
            'name' => 'Test Company Campaign',
            'subject' => 'Test with {{recipients}}',
            'content' => 'Hello {{recipients}}',
            'status' => Campaign::STATUS_DRAFT,
            'user_id' => $this->user->id,
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'type' => 'company'
        ];

        $campaign = $this->campaignService->createCampaign($data);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Test Company Campaign',
            'type' => 'company'
        ]);
    }

    /** @test */
    public function it_can_add_companies_to_company_campaign()
    {
        // Create campaign
        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $this->user->id
            ]);

        // Create companies
        $company1 = Company::factory()->create(['user_id' => $this->user->id]);
        $company2 = Company::factory()->create(['user_id' => $this->user->id]);

        // Add companies to campaign
        $count = $this->campaignService->addCompanies($campaign, [$company1->id, $company2->id]);

        $this->assertEquals(2, $count);
        $this->assertCount(2, $campaign->companies);
    }

    /** @test */
    public function it_processes_all_contacts_in_a_company_for_company_campaigns()
    {
        // Create campaign
        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $this->user->id
            ]);

        // Create company with contacts
        $company = Company::factory()->create(['user_id' => $this->user->id]);
        
        // Create contacts for the company
        $contact1 = Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'email' => 'contact1@example.com'
        ]);
        
        $contact2 = Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'email' => 'contact2@example.com'
        ]);

        // Add company to campaign
        $this->campaignService->addCompanies($campaign, [$company->id]);

        // Process the campaign to generate campaign_contacts records
        $result = $this->campaignService->prepareCompanyContactsForProcessing($campaign);
        
        // Assert contacts were added to campaign
        $this->assertEquals(2, $result);
        $this->assertDatabaseHas('campaign_contacts', [
            'campaign_id' => $campaign->id,
            'contact_id' => $contact1->id,
            'status' => 'pending'
        ]);
        $this->assertDatabaseHas('campaign_contacts', [
            'campaign_id' => $campaign->id,
            'contact_id' => $contact2->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_get_company_recipients_list_for_template()
    {
        // Create campaign
        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $this->user->id
            ]);

        // Create company with contacts
        $company = Company::factory()->create(['user_id' => $this->user->id]);
        
        // Create contacts for the company
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'first_name' => 'Doug',
            'last_name' => 'Steinberg',
            'email' => 'doug@example.com'
        ]);
        
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'first_name' => 'Angela',
            'last_name' => 'Todd',
            'email' => 'angela@example.com'
        ]);

        // Add company to campaign
        $this->campaignService->addCompanies($campaign, [$company->id]);

        // Get recipients for the company
        $recipientsList = $this->campaignService->getRecipientsListForCompany($company);
        
        // Assert correct format for recipients
        $this->assertEquals('Doug and Angela', $recipientsList);
    }

    /** @test */
    public function it_uses_last_names_when_first_names_conflict_in_recipients_list()
    {
        // Create campaign
        $campaign = Campaign::factory()
            ->companyCampaign()
            ->create([
                'user_id' => $this->user->id
            ]);

        // Create company with contacts
        $company = Company::factory()->create(['user_id' => $this->user->id]);
        
        // Create contacts with duplicate first names
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'first_name' => 'Doug',
            'last_name' => 'Steinberg',
            'email' => 'doug.s@example.com'
        ]);
        
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'first_name' => 'Doug',
            'last_name' => 'Todd',
            'email' => 'doug.t@example.com'
        ]);
        
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'first_name' => 'Angela',
            'last_name' => 'Smith',
            'email' => 'angela@example.com'
        ]);

        // Add company to campaign
        $this->campaignService->addCompanies($campaign, [$company->id]);

        // Get recipients for the company
        $recipientsList = $this->campaignService->getRecipientsListForCompany($company);
        
        // Assert correct format with last names for duplicate first names
        $this->assertEquals('Doug Steinberg, Doug Todd and Angela', $recipientsList);
    }
}