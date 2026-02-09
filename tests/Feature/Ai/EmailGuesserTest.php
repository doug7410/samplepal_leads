<?php

use App\Ai\Agents\EmailGuesser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('guesses emails based on company patterns', function () {
    EmailGuesser::fake(function () {
        return [
            'pattern' => 'first.last@domain',
            'confidence' => 'high',
            'guesses' => [
                ['contact_id' => 5, 'guessed_email' => 'john.doe@acme.com'],
            ],
        ];
    });

    $response = (new EmailGuesser)->prompt('Analyze email patterns and guess missing emails: {}');

    expect($response['pattern'])->toBe('first.last@domain');
    expect($response['confidence'])->toBe('high');
    expect($response['guesses'])->toHaveCount(1);
    expect($response['guesses'][0]['guessed_email'])->toBe('john.doe@acme.com');
});

it('can be faked with auto-generated responses', function () {
    EmailGuesser::fake();

    $response = (new EmailGuesser)->prompt('Analyze patterns');

    expect($response)->not->toBeNull();

    EmailGuesser::assertPrompted(fn ($prompt) => $prompt->contains('Analyze'));
});

it('returns confidence levels', function () {
    EmailGuesser::fake(function () {
        return [
            'pattern' => 'flast@domain',
            'confidence' => 'low',
            'guesses' => [],
        ];
    });

    $response = (new EmailGuesser)->prompt('Analyze patterns');

    expect($response['confidence'])->toBe('low');
    expect($response['guesses'])->toBeEmpty();
});
