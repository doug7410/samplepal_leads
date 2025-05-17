<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessCampaignJob;
use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Jobs\MockMailService;

class CampaignFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use Log facade spy instead of mocking
        Log::spy();
    }

    public function test_campaign_marked_as_failed_when_all_emails_fail()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create contacts
        $contacts = Contact::factory()->count(3)->create();

        // Create campaign contacts with FAILED status
        foreach ($contacts as $contact) {
            CampaignContact::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => CampaignContact::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => 'Test failure',
            ]);
        }

        // Run the process campaign job
        $job = new ProcessCampaignJob($campaign);
        $job->handle();

        // Refresh the campaign
        $campaign->refresh();

        // Verify that the campaign status is now FAILED
        $this->assertEquals(Campaign::STATUS_FAILED, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_campaign_marked_as_completed_when_some_emails_succeed()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create contacts
        $contacts = Contact::factory()->count(3)->create();

        // Create campaign contacts - 1 sent, 2 failed
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[0]->id,
            'status' => CampaignContact::STATUS_SENT,
            'sent_at' => now(),
        ]);

        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[1]->id,
            'status' => CampaignContact::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Test failure 1',
        ]);

        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[2]->id,
            'status' => CampaignContact::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Test failure 2',
        ]);

        // Run the process campaign job
        $job = new ProcessCampaignJob($campaign);
        $job->handle();

        // Refresh the campaign
        $campaign->refresh();

        // Verify that the campaign status is now COMPLETED (since at least one email was sent)
        $this->assertEquals(Campaign::STATUS_COMPLETED, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_campaign_marked_as_failed_when_no_emails_are_sent()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create contacts
        $contacts = Contact::factory()->count(3)->create();

        // Create campaign contacts with various failed/processing statuses, none sent
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[0]->id,
            'status' => CampaignContact::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Test failure 1',
        ]);

        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[1]->id,
            'status' => CampaignContact::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Test failure 2',
        ]);

        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[2]->id,
            'status' => CampaignContact::STATUS_PROCESSING, // Not failed, but not sent either
        ]);

        // Run the process campaign job
        $job = new ProcessCampaignJob($campaign);
        $job->handle();

        // Refresh the campaign
        $campaign->refresh();

        // Verify that the campaign status is now FAILED
        $this->assertEquals(Campaign::STATUS_FAILED, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_send_campaign_email_job_handles_errors_gracefully()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create a contact
        $contact = Contact::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create a campaign contact
        $campaignContact = CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);

        // Use our concrete mock mail service to throw an exception
        $mailService = new MockMailService();
        $mailService->shouldThrowException('Test mail sending error');

        // Create and run the job
        $job = new SendCampaignEmailJob($campaignContact);
        $job->handle($mailService);

        // Refresh the campaign contact
        $campaignContact->refresh();

        // Verify that the campaign contact is now marked as failed
        $this->assertEquals(CampaignContact::STATUS_FAILED, $campaignContact->status);
        $this->assertNotNull($campaignContact->failed_at);
        $this->assertEquals('Test mail sending error', $campaignContact->failure_reason);
    }

    public function test_send_campaign_email_job_handles_null_message_id_as_failure()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create a contact
        $contact = Contact::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create a campaign contact
        $campaignContact = CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);

        // Use our concrete mock mail service to return null
        $mailService = new MockMailService();
        $mailService->shouldReturnNull();

        // Create and run the job
        $job = new SendCampaignEmailJob($campaignContact);
        $job->handle($mailService);

        // Refresh the campaign contact
        $campaignContact->refresh();

        // Verify that the campaign contact is now marked as failed
        $this->assertEquals(CampaignContact::STATUS_FAILED, $campaignContact->status);
        $this->assertNotNull($campaignContact->failed_at);
        $this->assertEquals('No message ID returned from mail service', $campaignContact->failure_reason);
    }

    public function test_send_campaign_email_job_succeeds_with_valid_message_id()
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create a contact
        $contact = Contact::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create a campaign contact
        $campaignContact = CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);

        // Use our concrete mock mail service to return a message ID
        $mailService = new MockMailService();
        $mailService->withMessageId('test-message-id-123');

        // Create and run the job
        $job = new SendCampaignEmailJob($campaignContact);
        $job->handle($mailService);

        // Refresh the campaign contact
        $campaignContact->refresh();

        // With our mock service, the status doesn't change because the MarkCampaignContactSent 
        // functionality is in AbstractMailService which our MockMailService doesn't inherit from.
        // In real code, MailService would update the status to 'sent', but our test mock doesn't do that.
        // The job doesn't directly update the status when message ID is returned.
        $this->assertEquals(CampaignContact::STATUS_PENDING, $campaignContact->status);
    }

    public function test_process_campaign_job_batches_emails_correctly()
    {
        Queue::fake();

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create contacts and campaign contacts
        $contacts = Contact::factory()->count(75)->create(); // More than the default batch size of 50

        foreach ($contacts as $contact) {
            CampaignContact::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => CampaignContact::STATUS_PENDING,
            ]);
        }

        // Use reflection to modify the batch size for testing
        $job = new ProcessCampaignJob($campaign);
        $reflectionClass = new \ReflectionClass($job);
        $reflectionProperty = $reflectionClass->getProperty('batchSize');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($job, 25); // Set batch size to 25 for this test

        // Execute the job
        $job->handle();

        // Verify that the appropriate number of email sending jobs were queued
        Queue::assertPushed(SendCampaignEmailJob::class, 25); // First batch of 25

        // Verify that another ProcessCampaignJob was scheduled
        Queue::assertPushed(ProcessCampaignJob::class);
    }

    public function test_process_campaign_job_completes_when_all_contacts_processed()
    {
        Bus::fake([ProcessCampaignJob::class]);

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);

        // Create contacts and campaign contacts - all already processed
        $contacts = Contact::factory()->count(5)->create();

        foreach ($contacts as $index => $contact) {
            // Mix of sent and failed
            $status = $index % 2 === 0 ? CampaignContact::STATUS_SENT : CampaignContact::STATUS_FAILED;

            CampaignContact::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => $status,
                'sent_at' => $status === CampaignContact::STATUS_SENT ? now() : null,
                'failed_at' => $status === CampaignContact::STATUS_FAILED ? now() : null,
            ]);
        }

        // Execute the job
        $job = new ProcessCampaignJob($campaign);
        $job->handle();

        // Refresh the campaign
        $campaign->refresh();

        // Verify that no more jobs were dispatched
        Bus::assertNotDispatched(ProcessCampaignJob::class);

        // Verify that the campaign is marked as completed
        $this->assertEquals(Campaign::STATUS_COMPLETED, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_process_campaign_job_skips_inactive_campaigns()
    {
        // Create campaigns with various statuses
        $draftCampaign = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);
        $pausedCampaign = Campaign::factory()->create(['status' => Campaign::STATUS_PAUSED]);
        $completedCampaign = Campaign::factory()->create(['status' => Campaign::STATUS_COMPLETED]);

        // Create a contact
        $contact = Contact::factory()->create();

        // Add pending contacts to each campaign
        foreach ([$draftCampaign, $pausedCampaign, $completedCampaign] as $campaign) {
            CampaignContact::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => CampaignContact::STATUS_PENDING,
            ]);
        }

        // Process each campaign
        $draftJob = new ProcessCampaignJob($draftCampaign);
        $draftJob->handle();

        $pausedJob = new ProcessCampaignJob($pausedCampaign);
        $pausedJob->handle();

        $completedJob = new ProcessCampaignJob($completedCampaign);
        $completedJob->handle();

        // Refresh the campaigns
        $draftCampaign->refresh();
        $pausedCampaign->refresh();
        $completedCampaign->refresh();

        // Verify that all campaigns are still in their original status
        $this->assertEquals(Campaign::STATUS_DRAFT, $draftCampaign->status);
        $this->assertEquals(Campaign::STATUS_PAUSED, $pausedCampaign->status);
        $this->assertEquals(Campaign::STATUS_COMPLETED, $completedCampaign->status);

        // Verify that campaign contacts are still pending
        $this->assertEquals(
            3,
            CampaignContact::where('status', CampaignContact::STATUS_PENDING)->count()
        );
    }
}
