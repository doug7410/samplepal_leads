<?php

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Set up a clean database state for each test
    // Use RefreshDatabase trait instead of migrate:fresh to avoid VACUUM issues with SQLite
});

it('can create a campaign with valid attributes', function () {
    $campaign = Campaign::factory()->make();

    expect($campaign)->toBeInstanceOf(Campaign::class)
        ->and($campaign->name)->not->toBeEmpty()
        ->and($campaign->status)->toBe(Campaign::STATUS_DRAFT);

    $campaign->save();

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'name' => $campaign->name,
    ]);
});

it('can be created with mass assignment', function () {
    $user = User::factory()->create();

    $attributes = [
        'name' => 'Test Campaign',
        'description' => 'A test campaign description',
        'subject' => 'Test Subject Line',
        'content' => 'This is the email content for the test campaign.',
        'from_email' => 'from@example.com',
        'from_name' => 'Test Sender',
        'reply_to' => 'reply@example.com',
        'status' => Campaign::STATUS_DRAFT,
        'user_id' => $user->id,
        'filter_criteria' => ['manufacturer' => 'Acuity'],
    ];

    $campaign = Campaign::create($attributes);

    expect($campaign)->toBeInstanceOf(Campaign::class)
        ->and($campaign->name)->toBe('Test Campaign')
        ->and($campaign->subject)->toBe('Test Subject Line')
        ->and($campaign->filter_criteria)->toBe(['manufacturer' => 'Acuity']);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'name' => 'Test Campaign',
    ]);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    expect($campaign->user)->toBeInstanceOf(User::class)
        ->and($campaign->user->id)->toBe($user->id);
});

it('has a many-to-many relationship with contacts', function () {
    $campaign = Campaign::factory()->create();
    $contact1 = Contact::factory()->create();
    $contact2 = Contact::factory()->create();

    // Attach contacts to campaign
    $campaign->contacts()->attach([
        $contact1->id => ['status' => 'pending'],
        $contact2->id => ['status' => 'pending'],
    ]);

    expect($campaign->contacts)->toHaveCount(2)
        ->and($campaign->contacts->contains($contact1))->toBeTrue()
        ->and($campaign->contacts->contains($contact2))->toBeTrue();

    // Check the pivot table data
    $this->assertDatabaseHas('campaign_contacts', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact1->id,
        'status' => 'pending',
    ]);
});

it('casts json filter criteria correctly', function () {
    $filterCriteria = [
        'manufacturer' => 'Acuity',
        'state' => ['CA', 'NY'],
        'relevance_score' => ['min' => 5],
    ];

    $campaign = Campaign::factory()->create([
        'filter_criteria' => $filterCriteria,
    ]);

    // Reload the model from database
    $campaign->refresh();

    expect($campaign->filter_criteria)->toBe($filterCriteria)
        ->and($campaign->filter_criteria)->toBeArray()
        ->and($campaign->filter_criteria['manufacturer'])->toBe('Acuity')
        ->and($campaign->filter_criteria['state'])->toBe(['CA', 'NY']);
});

it('casts timestamps correctly', function () {
    $scheduledAt = now()->addDay();
    $completedAt = now()->addDays(2);

    $campaign = Campaign::factory()->create([
        'scheduled_at' => $scheduledAt,
        'completed_at' => $completedAt,
    ]);

    expect($campaign->scheduled_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($campaign->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($campaign->scheduled_at->toDateTimeString())->toBe($scheduledAt->toDateTimeString())
        ->and($campaign->completed_at->toDateTimeString())->toBe($completedAt->toDateTimeString());
});

it('can be created with different statuses using factory states', function () {
    $draftCampaign = Campaign::factory()->create();
    expect($draftCampaign->status)->toBe(Campaign::STATUS_DRAFT)
        ->and($draftCampaign->scheduled_at)->toBeNull();

    $scheduledCampaign = Campaign::factory()->scheduled()->create();
    expect($scheduledCampaign->status)->toBe(Campaign::STATUS_SCHEDULED)
        ->and($scheduledCampaign->scheduled_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

    $inProgressCampaign = Campaign::factory()->inProgress()->create();
    expect($inProgressCampaign->status)->toBe(Campaign::STATUS_IN_PROGRESS);

    $completedCampaign = Campaign::factory()->completed()->create();
    expect($completedCampaign->status)->toBe(Campaign::STATUS_COMPLETED)
        ->and($completedCampaign->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

    $failedCampaign = Campaign::factory()->failed()->create();
    expect($failedCampaign->status)->toBe(Campaign::STATUS_FAILED)
        ->and($failedCampaign->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
