<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('displays contacts with job_title_category', function () {
    $company = Company::factory()->create(['company_name' => 'Acme Corp']);
    Contact::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'job_title' => 'Quality Manager',
        'job_title_category' => 'Quality',
    ]);

    $response = $this->actingAs($this->user)->get(route('contacts.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 1)
        ->where('contacts.0.job_title_category', 'Quality')
    );
});

it('returns all contacts without filters', function () {
    $company = Company::factory()->create();
    Contact::factory()->count(3)->create(['company_id' => $company->id]);

    $response = $this->actingAs($this->user)->get(route('contacts.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 3)
    );
});

it('filters contacts by company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    Contact::factory()->count(2)->create(['company_id' => $company1->id]);
    Contact::factory()->create(['company_id' => $company2->id]);

    $response = $this->actingAs($this->user)->get(route('contacts.index', ['company_id' => $company1->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 2)
    );
});

it('filters contacts by job title', function () {
    $company = Company::factory()->create();
    Contact::factory()->count(2)->create(['company_id' => $company->id, 'job_title' => 'Quality Manager']);
    Contact::factory()->create(['company_id' => $company->id, 'job_title' => 'Sales Rep']);

    $response = $this->actingAs($this->user)->get(route('contacts.index', ['job_title' => 'Quality Manager']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 2)
    );
});

it('filters contacts by job category', function () {
    $company = Company::factory()->create();
    Contact::factory()->count(2)->create(['company_id' => $company->id, 'job_title_category' => 'Quality']);
    Contact::factory()->create(['company_id' => $company->id, 'job_title_category' => 'Sales']);

    $response = $this->actingAs($this->user)->get(route('contacts.index', ['job_title_category' => 'Quality']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 2)
    );
});

it('filters contacts with emails', function () {
    $company = Company::factory()->create();
    Contact::factory()->count(2)->create(['company_id' => $company->id, 'email' => 'test@example.com']);
    Contact::factory()->create(['company_id' => $company->id, 'email' => null]);
    Contact::factory()->create(['company_id' => $company->id, 'email' => '']);

    $response = $this->actingAs($this->user)->get(route('contacts.index', ['has_email' => 'with']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 2)
    );
});

it('filters contacts without emails', function () {
    $company = Company::factory()->create();
    Contact::factory()->count(2)->create(['company_id' => $company->id, 'email' => 'test@example.com']);
    Contact::factory()->create(['company_id' => $company->id, 'email' => null]);
    Contact::factory()->create(['company_id' => $company->id, 'email' => '']);

    $response = $this->actingAs($this->user)->get(route('contacts.index', ['has_email' => 'without']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('contacts', 2)
    );
});

it('passes job titles and job categories to the frontend', function () {
    $company = Company::factory()->create();
    Contact::factory()->create(['company_id' => $company->id, 'job_title' => 'Quality Manager', 'job_title_category' => 'Quality']);
    Contact::factory()->create(['company_id' => $company->id, 'job_title' => 'Sales Rep', 'job_title_category' => 'Sales']);

    $response = $this->actingAs($this->user)->get(route('contacts.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/index')
        ->has('jobTitles', 2)
        ->has('jobCategories', 2)
    );
});
