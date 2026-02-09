<?php

use App\Ai\Agents\JobCategoryClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('classifies job titles into correct categories', function () {
    JobCategoryClassifier::fake(function () {
        return [
            'classifications' => [
                ['id' => 1, 'category' => 'Principal'],
                ['id' => 2, 'category' => 'Sales'],
                ['id' => 3, 'category' => null],
            ],
        ];
    });

    $response = (new JobCategoryClassifier)->prompt('Classify these contacts: [{"id":1,"job_title":"President"},{"id":2,"job_title":"Specification Sales"},{"id":3,"job_title":"Controls Engineer"}]');

    expect($response['classifications'])->toHaveCount(3);
    expect($response['classifications'][0]['category'])->toBe('Principal');
    expect($response['classifications'][1]['category'])->toBe('Sales');
    expect($response['classifications'][2]['category'])->toBeNull();
});

it('can be faked with auto-generated responses', function () {
    JobCategoryClassifier::fake();

    $response = (new JobCategoryClassifier)->prompt('Classify these contacts: [{"id":1,"job_title":"Owner"}]');

    expect($response)->not->toBeNull();

    JobCategoryClassifier::assertPrompted(fn ($prompt) => $prompt->contains('Classify'));
});

it('asserts prompts were received', function () {
    JobCategoryClassifier::fake();

    (new JobCategoryClassifier)->prompt('Classify these contacts');

    JobCategoryClassifier::assertPrompted('Classify these contacts');
});
