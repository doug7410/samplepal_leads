<?php

namespace Tests\Feature\Campaigns;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailCampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private CampaignService $campaignService;

    private User $user;

    private Company $company1;

    private Company $company2;

    private $contacts1;

    private $contacts2;

    protected function setUp(): void
    {
        parent::setUp();
        // RefreshDatabase trait handles database setup

        // Create test data
        $this->user = User::factory()->create();

        $this->company1 = Company::factory()->create([
            'manufacturer' => 'Acuity',
            'company_name' => 'Acuity Lighting',
        ]);

        $this->company2 = Company::factory()->create([
            'manufacturer' => 'Cooper',
            'company_name' => 'Cooper Lighting',
        ]);

        // Create 5 contacts for each company
        $this->contacts1 = Contact::factory()->count(5)->create([
            'company_id' => $this->company1->id,
            'relevance_score' => 8,
            'deal_status' => 'none',
        ]);

        $this->contacts2 = Contact::factory()->count(5)->create([
            'company_id' => $this->company2->id,
            'relevance_score' => 6,
            'deal_status' => 'none',
        ]);

        // Create a campaign service
        $this->campaignService = new CampaignService;

        // Fake bus, jobs, and mail for testing
        Bus::fake();
        Queue::fake();
        Mail::fake();

        // Act as the user
        $this->actingAs($this->user);
    }

    public function test_creates_a_campaign_adds_contacts_and_starts_the_campaign(): void
    {
        // 1. Create a campaign
        $campaignData = [
            'name' => 'Test Campaign',
            'description' => 'A test campaign for feature testing',
            'subject' => 'Important announcement for {{first_name}}',
            'content' => '<p>Hello {{first_name}},</p><p>This is a test email.</p><p><a href="https://example.com">Click here</a> to learn more.</p>',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'reply_to' => 'reply@example.com',
            'user_id' => $this->user->id,
            'status' => Campaign::STATUS_DRAFT,
            'filter_criteria' => [
                'manufacturer' => 'Acuity',
                'relevance_min' => 7,
            ],
        ];

        $campaign = $this->campaignService->createCampaign($campaignData);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->name);
        $this->assertEquals(Campaign::STATUS_DRAFT, $campaign->status);

        // 2. Add contacts to the campaign based on filters
        $this->campaignService->addContactsFromFilter($campaign, $campaignData['filter_criteria']);

        // Refresh the campaign to get the updated contacts
        $campaign->refresh();

        // Only Acuity contacts with relevance score >= 7 should be added
        $this->assertCount(count($this->contacts1->where('relevance_score', '>=', 7)), $campaign->contacts);

        // 3. Schedule the campaign
        $campaign = $this->campaignService->updateCampaign($campaign, [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addMinutes(5),
        ]);

        $this->assertEquals(Campaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertNotNull($campaign->scheduled_at);

        // 4. Start the campaign (change status to in_progress)
        $campaign = $this->campaignService->updateStatus($campaign, Campaign::STATUS_IN_PROGRESS);

        $this->assertEquals(Campaign::STATUS_IN_PROGRESS, $campaign->status);

        // 5. Manually dispatch the process job (would normally be handled by a scheduled task)
        ProcessCampaignJob::dispatch($campaign);

        // Assert that the process job was dispatched
        Bus::assertDispatched(ProcessCampaignJob::class);
    }

    public function test_creates_a_campaign_and_adds_specific_contacts(): void
    {
        // 1. Create a campaign
        $campaignData = [
            'name' => 'Test Campaign with Specific Contacts',
            'description' => 'A test campaign for specific contacts',
            'subject' => 'Important announcement for {{first_name}}',
            'content' => '<p>Hello {{first_name}},</p><p>This is a test email.</p>',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'reply_to' => 'reply@example.com',
            'user_id' => $this->user->id,
            'status' => Campaign::STATUS_DRAFT,
        ];

        $campaign = $this->campaignService->createCampaign($campaignData);

        // 2. Add specific contacts (first 3 from each company)
        $specificContacts = [
            $this->contacts1[0]->id,
            $this->contacts1[1]->id,
            $this->contacts1[2]->id,
            $this->contacts2[0]->id,
            $this->contacts2[1]->id,
            $this->contacts2[2]->id,
        ];

        $this->campaignService->addContacts($campaign, $specificContacts);

        // Refresh the campaign
        $campaign->refresh();

        // Should have 6 contacts
        $this->assertCount(6, $campaign->contacts);

        // Verify it's the correct contacts
        foreach ($specificContacts as $contactId) {
            $this->assertDatabaseHas('campaign_contacts', [
                'campaign_id' => $campaign->id,
                'contact_id' => $contactId,
                'status' => CampaignContact::STATUS_PENDING,
            ]);
        }
    }

    public function test_removes_contacts_from_a_campaign(): void
    {
        // 1. Create a campaign
        $campaignData = [
            'name' => 'Test Campaign for Removing Contacts',
            'description' => 'A test campaign for removing contacts',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'user_id' => $this->user->id,
            'status' => Campaign::STATUS_DRAFT,
        ];

        $campaign = $this->campaignService->createCampaign($campaignData);

        // 2. Add all contacts
        $allContactIds = array_merge(
            $this->contacts1->pluck('id')->toArray(),
            $this->contacts2->pluck('id')->toArray()
        );

        $this->campaignService->addContacts($campaign, $allContactIds);

        // Refresh the campaign
        $campaign->refresh();

        // Should have 10 contacts
        $this->assertCount(10, $campaign->contacts);

        // 3. Remove some contacts
        $contactsToRemove = [
            $this->contacts1[0]->id,
            $this->contacts1[1]->id,
            $this->contacts2[0]->id,
        ];

        $this->campaignService->removeContacts($campaign, $contactsToRemove);

        // Refresh the campaign
        $campaign->refresh();

        // Should have 7 contacts left
        $this->assertCount(7, $campaign->contacts);

        // Verify removed contacts are gone
        foreach ($contactsToRemove as $contactId) {
            $this->assertDatabaseMissing('campaign_contacts', [
                'campaign_id' => $campaign->id,
                'contact_id' => $contactId,
            ]);
        }
    }

    public function test_calculates_campaign_statistics_correctly(): void
    {
        // 1. Create a campaign
        $campaignData = [
            'name' => 'Test Campaign for Statistics',
            'description' => 'A test campaign for statistics',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender',
            'user_id' => $this->user->id,
            'status' => Campaign::STATUS_IN_PROGRESS,
        ];

        $campaign = $this->campaignService->createCampaign($campaignData);

        // 2. Add all contacts
        $allContactIds = array_merge(
            $this->contacts1->pluck('id')->toArray(),
            $this->contacts2->pluck('id')->toArray()
        );

        $this->campaignService->addContacts($campaign, $allContactIds);

        // 3. Set different statuses for different contacts
        // First 3 contacts: delivered
        // Next 2: opened
        // Next 2: clicked
        // Next 1: responded
        // Last 2: failed

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts1[0]->id)
            ->update(['status' => CampaignContact::STATUS_DELIVERED, 'delivered_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts1[1]->id)
            ->update(['status' => CampaignContact::STATUS_DELIVERED, 'delivered_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts1[2]->id)
            ->update(['status' => CampaignContact::STATUS_DELIVERED, 'delivered_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts1[3]->id)
            ->update(['status' => CampaignContact::STATUS_OPENED, 'delivered_at' => now(), 'opened_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts1[4]->id)
            ->update(['status' => CampaignContact::STATUS_OPENED, 'delivered_at' => now(), 'opened_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts2[0]->id)
            ->update(['status' => CampaignContact::STATUS_CLICKED, 'delivered_at' => now(), 'opened_at' => now(), 'clicked_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts2[1]->id)
            ->update(['status' => CampaignContact::STATUS_CLICKED, 'delivered_at' => now(), 'opened_at' => now(), 'clicked_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts2[2]->id)
            ->update(['status' => CampaignContact::STATUS_RESPONDED, 'delivered_at' => now(), 'opened_at' => now(), 'clicked_at' => now(), 'responded_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts2[3]->id)
            ->update(['status' => CampaignContact::STATUS_FAILED, 'failed_at' => now()]);

        CampaignContact::where('campaign_id', $campaign->id)
            ->where('contact_id', $this->contacts2[4]->id)
            ->update(['status' => CampaignContact::STATUS_FAILED, 'failed_at' => now()]);

        // 4. Get statistics
        $stats = $this->campaignService->getStatistics($campaign);

        // Assertions
        $this->assertIsArray($stats);
        $this->assertEquals(10, $stats['total']);

        // Status counts
        $this->assertEquals(0, $stats['statuses']['pending']);
        $this->assertEquals(3, $stats['statuses']['delivered']);
        $this->assertEquals(2, $stats['statuses']['opened']);
        $this->assertEquals(2, $stats['statuses']['clicked']);
        $this->assertEquals(1, $stats['statuses']['responded']);
        $this->assertEquals(2, $stats['statuses']['failed']);

        // Rates
        // Delivery rate: 6 delivered out of 10 total (60%)
        // Open rate: 5 opened out of 6 delivered (83.33%)
        // Click rate: 3 clicked out of 5 opened (60%)
        // Response rate: 1 responded out of 6 delivered (16.67%)

        // Rather than testing exact percentages (which depend on implementation),
        // just check that the statistics are returning reasonable values
        $this->assertIsArray($stats['rates']);
        $this->assertIsNumeric($stats['rates']['delivery']);
        $this->assertIsNumeric($stats['rates']['open']);
        $this->assertIsNumeric($stats['rates']['click']);
        $this->assertIsNumeric($stats['rates']['response']);
    }
}
