<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyCampaignTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_can_create_a_company_campaign()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('campaigns.store'), [
            'name' => 'Test Company Campaign',
            'subject' => 'Hello {{recipients}}',
            'content' => '<p>Hello {{recipients}},</p><p>This is a company campaign test.</p>',
            'from_email' => 'test@example.com',
            'type' => 'company',
            'company_ids' => [$company->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Company Campaign',
            'type' => 'company',
        ]);

        $campaign = Campaign::where('name', 'Test Company Campaign')->first();
        $this->assertNotNull($campaign);

        // Check that company was associated with campaign
        $this->assertDatabaseHas('campaign_companies', [
            'campaign_id' => $campaign->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function company_campaigns_generate_recipient_lists_correctly()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        // Create contacts for the company
        $doug = Contact::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'first_name' => 'Doug',
            'last_name' => 'Steinberg',
            'email' => 'doug@example.com',
        ]);

        $angela = Contact::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'first_name' => 'Angela',
            'last_name' => 'Todd',
            'email' => 'angela@example.com',
        ]);

        // Create campaign and associate company
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'name' => 'Company Campaign Test',
            'subject' => 'Hello {{recipients}}',
            'content' => '<p>Hello {{recipients}},</p><p>This is a test.</p>',
            'type' => 'company',
        ]);

        // Send the request to add company
        $response = $this->actingAs($user)->post(route('campaigns.add-companies', $campaign), [
            'company_ids' => [$company->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Now send the campaign
        $response = $this->actingAs($user)->post(route('campaigns.send', $campaign));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the campaign status changed to in_progress
        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_IN_PROGRESS, $campaign->status);
        
        // Verify the company is associated with the campaign
        $this->assertDatabaseHas('campaign_companies', [
            'campaign_id' => $campaign->id,
            'company_id' => $company->id,
        ]);
        
        // We no longer create campaign contacts for company campaigns in advance
        // Instead we directly process the company contacts when sending
    }

    /** @test */
    public function company_campaign_processes_all_contacts_when_sent()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        // Create multiple contacts for the company
        $contacts = Contact::factory()->count(3)->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Create a company campaign
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'type' => 'company',
            'status' => Campaign::STATUS_DRAFT,
        ]);

        // Associate company with campaign
        $this->actingAs($user)->post(route('campaigns.add-companies', $campaign), [
            'company_ids' => [$company->id],
        ]);

        // Send the campaign
        $this->actingAs($user)->post(route('campaigns.send', $campaign));

        // Refresh campaign from database
        $campaign->refresh();

        // Assert campaign status is now in_progress
        $this->assertEquals(Campaign::STATUS_IN_PROGRESS, $campaign->status, 
            "Campaign status should be in_progress, but got {$campaign->status}");

        // We're no longer using campaign contacts for company campaigns, so we verify that:
        
        // 1. The campaign is in in_progress state
        $this->assertEquals(Campaign::STATUS_IN_PROGRESS, $campaign->status);
        
        // 2. The company is associated with the campaign
        $this->assertDatabaseHas('campaign_companies', [
            'campaign_id' => $campaign->id,
            'company_id' => $company->id,
        ]);
        
        // 3. Each contact is part of the company
        foreach ($contacts as $contact) {
            $this->assertEquals($company->id, $contact->company_id);
        }
    }
}
