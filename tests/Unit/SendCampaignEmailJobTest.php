<?php

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\MailService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Fresh database for each test
    $this->artisan('migrate:fresh');

    // Create test data
    $this->campaign = Campaign::factory()->create([
        'status' => Campaign::STATUS_IN_PROGRESS,
        'subject' => 'Test Subject with {{first_name}}',
        'content' => 'Hello {{first_name}}, this is a test email from {{company}}.',
    ]);

    $this->contact = Contact::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
    ]);

    $this->campaignContact = CampaignContact::create([
        'campaign_id' => $this->campaign->id,
        'contact_id' => $this->contact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    // Fake the queue to prevent actual jobs from being dispatched
    Queue::fake();
});

it('skips processing when campaign is not in progress', function () {
    // Set campaign to draft status
    $this->campaign->status = Campaign::STATUS_DRAFT;
    $this->campaign->save();

    // Create a mock MailService that expects no calls
    $mailService = $this->createMock(MailService::class);
    $mailService->expects($this->never())->method('sendEmail');

    // Process the job
    $job = new SendCampaignEmailJob($this->campaignContact);
    $job->handle($mailService);

    // The campaign contact status should still be pending
    $this->campaignContact->refresh();
    expect($this->campaignContact->status)->toBe(CampaignContact::STATUS_PENDING);
});

it('skips processing when campaign contact is not pending', function () {
    // Set campaign contact to already sent
    $this->campaignContact->status = CampaignContact::STATUS_SENT;
    $this->campaignContact->sent_at = now();
    $this->campaignContact->save();

    // Create a mock MailService that expects no calls
    $mailService = $this->createMock(MailService::class);
    $mailService->expects($this->never())->method('sendEmail');

    // Process the job
    $job = new SendCampaignEmailJob($this->campaignContact);
    $job->handle($mailService);

    // The campaign contact status should remain as sent
    $this->campaignContact->refresh();
    expect($this->campaignContact->status)->toBe(CampaignContact::STATUS_SENT);
});

// Simple structure tests without checking implementation details
it('accepts a campaign contact in the constructor', function () {
    $job = new SendCampaignEmailJob($this->campaignContact);

    expect($job)->toBeInstanceOf(SendCampaignEmailJob::class);
});

it('implements the ShouldQueue interface', function () {
    $job = new SendCampaignEmailJob($this->campaignContact);

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});
