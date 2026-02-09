<?php

use App\Ai\Agents\EmailGuesser;
use App\Ai\Agents\JobCategoryClassifier;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('fails when no option is specified', function () {
    $this->artisan('app:enrich-contacts')
        ->assertFailed();
});

it('classifies job title categories', function () {
    JobCategoryClassifier::fake(function () {
        return [
            'classifications' => [
                ['id' => 1, 'category' => 'Principal'],
                ['id' => 2, 'category' => 'Sales'],
                ['id' => 3, 'category' => null],
            ],
        ];
    });

    $company = Company::factory()->create();
    Contact::factory()->create(['id' => 1, 'company_id' => $company->id, 'job_title' => 'President', 'job_title_category' => null]);
    Contact::factory()->create(['id' => 2, 'company_id' => $company->id, 'job_title' => 'Spec Sales Rep', 'job_title_category' => null]);
    Contact::factory()->create(['id' => 3, 'company_id' => $company->id, 'job_title' => 'Controls', 'job_title_category' => 'Sales']);

    $this->artisan('app:enrich-contacts --classify-categories')
        ->assertSuccessful();

    expect(Contact::find(1)->job_title_category)->toBe('Principal');
    expect(Contact::find(2)->job_title_category)->toBe('Sales');
    expect(Contact::find(3)->job_title_category)->toBeNull();
});

it('classifies categories in dry run without saving', function () {
    JobCategoryClassifier::fake(function () {
        return [
            'classifications' => [
                ['id' => 1, 'category' => 'Principal'],
            ],
        ];
    });

    $company = Company::factory()->create();
    Contact::factory()->create(['id' => 1, 'company_id' => $company->id, 'job_title' => 'President', 'job_title_category' => null]);

    $this->artisan('app:enrich-contacts --classify-categories --dry-run')
        ->assertSuccessful();

    expect(Contact::find(1)->job_title_category)->toBeNull();
});

it('validates company websites', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $company = Company::factory()->create(['website' => 'https://example.com']);

    $this->artisan('app:enrich-contacts --validate-websites')
        ->assertSuccessful();

    $company->refresh();
    expect($company->website_status)->toBe('reachable');
    expect($company->website_checked_at)->not->toBeNull();
});

it('validates websites in dry run without saving', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $company = Company::factory()->create(['website' => 'https://example.com']);

    $this->artisan('app:enrich-contacts --validate-websites --dry-run')
        ->assertSuccessful();

    $company->refresh();
    expect($company->website_status)->toBeNull();
});

it('guesses missing emails', function () {
    EmailGuesser::fake(function () {
        return [
            'pattern' => 'first.last@acme.com',
            'confidence' => 'high',
            'guesses' => [
                ['contact_id' => 2, 'guessed_email' => 'jane.smith@acme.com'],
            ],
        ];
    });

    $company = Company::factory()->create();
    Contact::factory()->create(['id' => 1, 'company_id' => $company->id, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@acme.com']);
    Contact::factory()->create(['id' => 2, 'company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => null]);

    $this->artisan('app:enrich-contacts --guess-emails')
        ->assertSuccessful();

    $contact = Contact::find(2);
    expect($contact->email)->toBe('jane.smith@acme.com');
    expect($contact->email_source)->toBe('ai_guessed');
});

it('skips email guessing for companies with no reference emails', function () {
    EmailGuesser::fake();

    $company = Company::factory()->create();
    Contact::factory()->create(['company_id' => $company->id, 'email' => null]);

    $this->artisan('app:enrich-contacts --guess-emails')
        ->assertSuccessful();

    EmailGuesser::assertNeverPrompted();
});

it('marks contacts at unreachable companies as unusable', function () {
    $unreachableCompany = Company::factory()->create(['website_status' => 'unreachable']);
    $reachableCompany = Company::factory()->create(['website_status' => 'reachable']);

    $unusableContact = Contact::factory()->create(['company_id' => $unreachableCompany->id, 'is_enrichment_unusable' => false]);
    $usableContact = Contact::factory()->create(['company_id' => $reachableCompany->id, 'is_enrichment_unusable' => false]);

    $this->artisan('app:enrich-contacts --mark-unusable')
        ->assertSuccessful();

    expect($unusableContact->fresh()->is_enrichment_unusable)->toBeTrue();
    expect($usableContact->fresh()->is_enrichment_unusable)->toBeFalse();
});

it('runs all tasks with --all flag', function () {
    JobCategoryClassifier::fake(function () {
        return [
            'classifications' => [
                ['id' => 1, 'category' => 'Sales'],
            ],
        ];
    });
    EmailGuesser::fake(function () {
        return [
            'pattern' => 'first@domain',
            'confidence' => 'medium',
            'guesses' => [],
        ];
    });
    Http::fake(['*' => Http::response('OK', 200)]);

    $company = Company::factory()->create(['website' => 'https://example.com']);
    Contact::factory()->create(['id' => 1, 'company_id' => $company->id, 'job_title' => 'Sales Rep']);

    $this->artisan('app:enrich-contacts --all')
        ->assertSuccessful();
});
