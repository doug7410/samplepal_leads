<?php

use App\Models\Company;
use App\Models\Contact;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Set up a clean database state for each test
    $this->artisan('migrate:fresh');
});

it('can create a company with valid attributes', function () {
    $company = Company::factory()->make();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->company_name)->not->toBeEmpty()
        ->and($company->manufacturer)->not->toBeEmpty();

    $company->save();

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'company_name' => $company->company_name,
    ]);
});

it('can be created with mass assignment', function () {
    $attributes = [
        'manufacturer' => 'Test Manufacturer',
        'company_name' => 'Test Company',
        'company_phone' => '555-1234',
        'address_line_1' => '123 Test St',
        'address_line_2' => 'Suite 100',
        'city_or_region' => 'Testville',
        'state' => 'TS',
        'zip_code' => '12345',
        'country' => 'Testland',
        'email' => 'info@test.com',
        'website' => 'https://test.com',
        'contact_name' => 'Test Contact',
        'contact_phone' => '555-5678',
        'contact_email' => 'contact@test.com',
    ];

    $company = Company::create($attributes);

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->company_name)->toBe('Test Company')
        ->and($company->manufacturer)->toBe('Test Manufacturer')
        ->and($company->address_line_1)->toBe('123 Test St');

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'company_name' => 'Test Company',
    ]);
});

it('has a one-to-many relationship with contacts', function () {
    $company = Company::factory()->create();
    $contact1 = Contact::factory()->create(['company_id' => $company->id]);
    $contact2 = Contact::factory()->create(['company_id' => $company->id]);

    expect($company->contacts)->toHaveCount(2)
        ->and($company->contacts->contains($contact1))->toBeTrue()
        ->and($company->contacts->contains($contact2))->toBeTrue();
});

it('casts timestamps to datetime', function () {
    $company = Company::factory()->create();

    expect($company->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($company->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('can update company attributes', function () {
    $company = Company::factory()->create();

    $company->update([
        'company_name' => 'Updated Company',
        'email' => 'updated@example.com',
    ]);

    $company->refresh();

    expect($company->company_name)->toBe('Updated Company')
        ->and($company->email)->toBe('updated@example.com');

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'company_name' => 'Updated Company',
        'email' => 'updated@example.com',
    ]);
});

it('can be filtered by manufacturer', function () {
    // Create companies with different manufacturers
    Company::factory()->create(['manufacturer' => 'Acuity']);
    Company::factory()->create(['manufacturer' => 'Cooper']);
    Company::factory()->create(['manufacturer' => 'Signify']);
    Company::factory()->create(['manufacturer' => 'Other']);

    $acuityCompanies = Company::where('manufacturer', 'Acuity')->get();
    $cooperCompanies = Company::where('manufacturer', 'Cooper')->get();

    expect($acuityCompanies)->toHaveCount(1)
        ->and($acuityCompanies->first()->manufacturer)->toBe('Acuity')
        ->and($cooperCompanies)->toHaveCount(1)
        ->and($cooperCompanies->first()->manufacturer)->toBe('Cooper');
});
