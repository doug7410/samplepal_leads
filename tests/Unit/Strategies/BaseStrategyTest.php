<?php

namespace Tests\Unit\Strategies;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

abstract class BaseStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the HTTP facade
        Http::fake();

        // Configure sample data paths
        $this->sampleDataPaths = [
            'acuity' => storage_path('app/acuity_response.json'),
            'cooper' => storage_path('app/cooper_response.json'),
            'signify' => storage_path('app/signify_response.json'),
        ];
    }

    /**
     * Load sample response data for a manufacturer
     */
    protected function getSampleResponseData(string $manufacturer): array
    {
        $path = $this->sampleDataPaths[$manufacturer] ?? null;

        if (! $path || ! File::exists($path)) {
            return [];
        }

        $content = File::get($path);

        return json_decode($content, true) ?? [];
    }

    /**
     * Mock configuration values for testing
     */
    protected function mockManufacturerConfig(string $manufacturer, array $config): void
    {
        // Mock the field mappings
        if (isset($config['field_mapping'])) {
            Config::set("manufacturers.field_mapping.{$manufacturer}", $config['field_mapping']);
        }

        // Mock the API config
        if (isset($config['api_config'])) {
            Config::set("manufacturers.api_config.{$manufacturer}", $config['api_config']);
        }

        // Mock major cities if needed
        if (isset($config['major_cities'])) {
            Config::set('manufacturers.major_cities', $config['major_cities']);
        }
    }

    /**
     * Set up a mock HTTP response
     */
    protected function mockHttpResponse(string $url, array $data, int $status = 200): void
    {
        Http::fake([
            $url => Http::response($data, $status),
        ]);
    }
}
