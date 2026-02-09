<?php

use App\Services\WebsiteValidatorService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('returns reachable for successful responses', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('reachable');
});

it('returns reachable for redirect responses', function () {
    Http::fake(['*' => Http::response('', 301)]);

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('reachable');
});

it('returns unreachable for server errors', function () {
    Http::fake(['*' => Http::response('Error', 500)]);

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('unreachable');
});

it('returns ssl_error for SSL exceptions', function () {
    Http::fake(fn () => throw new ConnectionException('SSL certificate problem'));

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('ssl_error');
});

it('returns timeout for timeout exceptions', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('timeout');
});

it('returns unreachable for generic connection exceptions', function () {
    Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

    $service = new WebsiteValidatorService;

    expect($service->validate('https://example.com'))->toBe('unreachable');
});

it('normalizes URLs without scheme', function () {
    Http::fake(['https://example.com' => Http::response('OK', 200)]);

    $service = new WebsiteValidatorService;

    expect($service->validate('example.com'))->toBe('reachable');
});
