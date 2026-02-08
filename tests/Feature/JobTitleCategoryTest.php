<?php

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('job_title_category column exists on contacts table', function () {
    $contact = Contact::factory()->create(['job_title_category' => 'Sales']);

    expect($contact->job_title_category)->toBe('Sales');
});

test('job_title_category is nullable', function () {
    $contact = Contact::factory()->create(['job_title_category' => null]);

    expect($contact->job_title_category)->toBeNull();
});

test('job_title_category is mass assignable', function () {
    $contact = Contact::factory()->create();

    $contact->update(['job_title_category' => 'Principal']);

    expect($contact->fresh()->job_title_category)->toBe('Principal');
});

test('job_title_category accepts all valid categories', function (string $category) {
    $contact = Contact::factory()->create(['job_title_category' => $category]);

    expect($contact->fresh()->job_title_category)->toBe($category);
})->with([
    'Principal',
    'Sales',
    'Operations',
    'Project Manager',
    'Other',
]);
