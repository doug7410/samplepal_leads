<?php

use App\Jobs\ProcessCampaignSegmentJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CampaignSegment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CampaignSegmentService;
use Illuminate\Support\Facades\Queue;

// ── Controller: Store (Create Segments) ──────────────────────────

it('creates segments and distributes contacts evenly', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $contacts = Contact::factory()->count(6)->create(['company_id' => $company->id]);
    foreach ($contacts as $contact) {
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);
    }

    $response = $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 3,
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('success');

    expect($campaign->segments()->count())->toBe(3);

    // Each segment should have 2 contacts (6 / 3)
    $campaign->segments->each(function ($segment) {
        expect($segment->campaignContacts()->count())->toBe(2);
    });
});

it('distributes contacts round-robin when not evenly divisible', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $contacts = Contact::factory()->count(5)->create(['company_id' => $company->id]);
    foreach ($contacts as $contact) {
        CampaignContact::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);
    }

    $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 3,
    ]);

    $segments = $campaign->segments()->orderBy('position')->get();
    $counts = $segments->map(fn ($s) => $s->campaignContacts()->count());

    // 5 contacts into 3 segments: 2, 2, 1
    expect($counts->toArray())->toBe([2, 2, 1]);
});

it('rejects creating segments for a non-draft campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create(['user_id' => $user->id]);
    $company = Company::factory()->create();
    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 2,
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
    expect($campaign->segments()->count())->toBe(0);
});

it('rejects creating segments for a campaign with no contacts', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 2,
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
});

it('validates number_of_segments is between 2 and 20', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 1,
    ])->assertSessionHasErrors('number_of_segments');

    $this->actingAs($user)->post(route('campaigns.segments.store', $campaign), [
        'number_of_segments' => 21,
    ])->assertSessionHasErrors('number_of_segments');
});

// ── Controller: Update (Edit Segment) ────────────────────────────

it('updates a segment subject and content', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->put(route('campaigns.segments.update', [$campaign, $segment]), [
        'name' => 'Updated Name',
        'subject' => 'New Subject',
        'content' => 'New Content',
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('success');

    $segment->refresh();
    expect($segment->name)->toBe('Updated Name');
    expect($segment->subject)->toBe('New Subject');
    expect($segment->content)->toBe('New Content');
});

it('allows clearing segment overrides to null', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->withOverrides()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $this->actingAs($user)->put(route('campaigns.segments.update', [$campaign, $segment]), [
        'subject' => null,
        'content' => null,
    ]);

    $segment->refresh();
    expect($segment->subject)->toBeNull();
    expect($segment->content)->toBeNull();
});

it('rejects editing a non-draft segment', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->completed()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->put(route('campaigns.segments.update', [$campaign, $segment]), [
        'subject' => 'Should Not Work',
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
});

// ── Controller: Destroy (Remove Segments) ────────────────────────

it('deletes all draft segments and clears contact references', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $campaignContact = CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->delete(route('campaigns.segments.destroy', $campaign));

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('success');

    expect($campaign->segments()->count())->toBe(0);
    expect($campaignContact->fresh()->campaign_segment_id)->toBeNull();
});

it('rejects deleting segments when any segment has been sent', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create(['user_id' => $user->id]);

    CampaignSegment::factory()->completed()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 2,
    ]);

    $response = $this->actingAs($user)->delete(route('campaigns.segments.destroy', $campaign));

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
    expect($campaign->segments()->count())->toBe(2);
});

// ── Controller: Send Segment ─────────────────────────────────────

it('sends a draft segment and dispatches the job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->post(route('campaigns.segments.send', [$campaign, $segment]));

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('success');

    Queue::assertPushed(ProcessCampaignSegmentJob::class);

    $segment->refresh();
    $campaign->refresh();
    expect($segment->status)->toBe(CampaignSegment::STATUS_IN_PROGRESS);
    expect($campaign->status)->toBe(Campaign::STATUS_IN_PROGRESS);
});

it('transitions campaign from draft to in_progress on first segment send', function () {
    Queue::fake();

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $segment1 = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);
    $segment2 = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 2,
    ]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment1->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    expect($campaign->status)->toBe(Campaign::STATUS_DRAFT);

    $this->actingAs($user)->post(route('campaigns.segments.send', [$campaign, $segment1]));

    $campaign->refresh();
    expect($campaign->status)->toBe(Campaign::STATUS_IN_PROGRESS);
});

it('rejects sending a non-draft segment', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->completed()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->post(route('campaigns.segments.send', [$campaign, $segment]));

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
});

it('rejects sending a segment when campaign is not in a sendable state', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->completed()->create(['user_id' => $user->id]);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->post(route('campaigns.segments.send', [$campaign, $segment]));

    $response->assertRedirect(route('campaigns.show', $campaign));
    $response->assertSessionHas('error');
});

// ── Service: Segment Statistics ──────────────────────────────────

it('calculates segment statistics correctly', function () {
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create();
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $contacts = Contact::factory()->count(4)->create(['company_id' => $company->id]);

    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contacts[0]->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_DELIVERED,
    ]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contacts[1]->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_CLICKED,
    ]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contacts[2]->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_FAILED,
    ]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contacts[3]->id,
        'campaign_segment_id' => $segment->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $stats = app(CampaignSegmentService::class)->getSegmentStatistics($segment);

    expect($stats['total'])->toBe(4);
    expect($stats['statuses']['delivered'])->toBe(1);
    expect($stats['statuses']['clicked'])->toBe(1);
    expect($stats['statuses']['failed'])->toBe(1);
    expect($stats['statuses']['pending'])->toBe(1);
    // Delivery rate: (delivered + clicked) / total = 2/4 = 50%
    expect($stats['rates']['delivery'])->toBe(50.0);
    // Click rate: clicked / delivered_count = 1/2 = 50%
    expect($stats['rates']['click'])->toBe(50.0);
});

// ── Service: Complete Segment ────────────────────────────────────

it('marks segment as completed and completes campaign when all segments done', function () {
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create();

    $segment1 = CampaignSegment::factory()->completed()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $segment2 = CampaignSegment::factory()->inProgress()->create([
        'campaign_id' => $campaign->id,
        'position' => 2,
    ]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment2->id,
        'status' => CampaignContact::STATUS_SENT,
    ]);

    app(CampaignSegmentService::class)->completeSegment($segment2);

    $segment2->refresh();
    $campaign->refresh();
    expect($segment2->status)->toBe(CampaignSegment::STATUS_COMPLETED);
    expect($campaign->status)->toBe(Campaign::STATUS_COMPLETED);
    expect($campaign->completed_at)->not->toBeNull();
});

it('marks campaign as failed when all segments failed', function () {
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create();

    $segment1 = CampaignSegment::factory()->failed()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    $segment2 = CampaignSegment::factory()->inProgress()->create([
        'campaign_id' => $campaign->id,
        'position' => 2,
    ]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment2->id,
        'status' => CampaignContact::STATUS_FAILED,
    ]);

    app(CampaignSegmentService::class)->completeSegment($segment2);

    $segment2->refresh();
    $campaign->refresh();
    expect($segment2->status)->toBe(CampaignSegment::STATUS_FAILED);
    expect($campaign->status)->toBe(Campaign::STATUS_FAILED);
});

it('does not complete campaign when some segments are still pending', function () {
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->inProgress()->create();

    $segment1 = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
        'status' => CampaignSegment::STATUS_DRAFT,
    ]);

    $segment2 = CampaignSegment::factory()->inProgress()->create([
        'campaign_id' => $campaign->id,
        'position' => 2,
    ]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'campaign_segment_id' => $segment2->id,
        'status' => CampaignContact::STATUS_SENT,
    ]);

    app(CampaignSegmentService::class)->completeSegment($segment2);

    $campaign->refresh();
    expect($campaign->status)->toBe(Campaign::STATUS_IN_PROGRESS);
});

// ── Model: Effective Subject/Content ─────────────────────────────

it('returns segment override subject when set', function () {
    $campaign = Campaign::factory()->create(['subject' => 'Campaign Subject']);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
        'subject' => 'Segment Override',
    ]);

    expect($segment->getEffectiveSubject())->toBe('Segment Override');
});

it('falls back to campaign subject when segment subject is null', function () {
    $campaign = Campaign::factory()->create(['subject' => 'Campaign Subject']);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
        'subject' => null,
    ]);

    expect($segment->getEffectiveSubject())->toBe('Campaign Subject');
});

it('returns segment override content when set', function () {
    $campaign = Campaign::factory()->create(['content' => 'Campaign Content']);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
        'content' => 'Segment Content',
    ]);

    expect($segment->getEffectiveContent())->toBe('Segment Content');
});

it('falls back to campaign content when segment content is null', function () {
    $campaign = Campaign::factory()->create(['content' => 'Campaign Content']);
    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
        'content' => null,
    ]);

    expect($segment->getEffectiveContent())->toBe('Campaign Content');
});

// ── Campaign Show: Segments Visible ──────────────────────────────

it('shows segments on campaign show page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $segment = CampaignSegment::factory()->create([
        'campaign_id' => $campaign->id,
        'position' => 1,
    ]);

    CampaignContact::where('campaign_id', $campaign->id)
        ->where('contact_id', $contact->id)
        ->update(['campaign_segment_id' => $segment->id]);

    $response = $this->actingAs($user)->get(route('campaigns.show', $campaign));

    $response->assertSuccessful();

    $props = $response->original->getData()['page']['props'];
    expect($props['campaign']['segments'])->toHaveCount(1);
    expect($props['campaign']['segments'][0]['name'])->toBe($segment->name);
});

// ── Backward Compatibility ───────────────────────────────────────

it('campaign show page works without segments', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $contact = Contact::factory()->create(['company_id' => $company->id]);
    CampaignContact::create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => CampaignContact::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->get(route('campaigns.show', $campaign));

    $response->assertSuccessful();

    $props = $response->original->getData()['page']['props'];
    expect($props['campaign']['segments'])->toBeEmpty();
    expect($props['segmentStatistics'])->toBeEmpty();
});
