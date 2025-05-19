<?php

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\MailService;
use App\Services\MailServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Unit\Services\MockMailService;

class CompanyCampaignEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Prevent actual emails from being sent
        Mail::fake();
    }

    /** @test */
    public function it_can_send_emails_to_all_contacts_in_a_company()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a company
        $company = Company::factory()->create([
            'user_id' => $user->id
        ]);

        // Create contacts for the company
        $contacts = Contact::factory()->count(3)->create([
            'company_id' => $company->id,
            'user_id' => $user->id
        ]);

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'type' => Campaign::TYPE_COMPANY,
            'status' => Campaign::STATUS_IN_PROGRESS
        ]);

        // Associate the company with the campaign
        $campaign->companies()->attach($company->id);

        // Mock the mail service
        $mockMailService = new MockMailService();
        $this->app->instance(MailServiceInterface::class, $mockMailService);

        // Send emails to the company
        $result = $mockMailService->sendEmailToCompany($campaign, $company);

        // Verify results
        $this->assertCount($contacts->count(), $result);
        foreach ($contacts as $contact) {
            $this->assertArrayHasKey($contact->id, $result);
            $this->assertNotNull($result[$contact->id]);
        }
    }

    /** @test */
    public function it_handles_empty_company_contacts_gracefully()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a company with no contacts
        $company = Company::factory()->create([
            'user_id' => $user->id
        ]);

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'type' => Campaign::TYPE_COMPANY,
            'status' => Campaign::STATUS_IN_PROGRESS
        ]);

        // Associate the company with the campaign
        $campaign->companies()->attach($company->id);

        // Get the mail service
        $mailService = $this->app->make(MailServiceInterface::class);

        // Send emails to the company (should return empty array)
        $result = $mailService->sendEmailToCompany($campaign, $company);

        // Verify results
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_skips_contacts_without_email_addresses()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a company
        $company = Company::factory()->create([
            'user_id' => $user->id
        ]);

        // Create contacts for the company, some with email and some without
        $contactWithEmail = Contact::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'email' => 'valid@example.com'
        ]);
        
        $contactWithoutEmail = Contact::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'email' => null
        ]);

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'type' => Campaign::TYPE_COMPANY,
            'status' => Campaign::STATUS_IN_PROGRESS
        ]);

        // Associate the company with the campaign
        $campaign->companies()->attach($company->id);

        // Mock the mail service
        $mockMailService = new MockMailService();
        $this->app->instance(MailServiceInterface::class, $mockMailService);

        // Send emails to the company
        $result = $mockMailService->sendEmailToCompany($campaign, $company);

        // Verify results
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($contactWithEmail->id, $result);
        $this->assertArrayNotHasKey($contactWithoutEmail->id, $result);
    }
}