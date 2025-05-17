<?php

namespace App\Strategies\Manufacturers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignifyStrategy extends BaseManufacturerStrategy
{
    /**
     * List of major cities to search
     */
    protected array $cities;

    /**
     * Constructor to initialize class properties
     */
    public function __construct()
    {
        $this->manufacturerName = 'Signify';
        $this->baseUrl = config('manufacturers.api_config.signify.agents_url');
        $this->headers = config('manufacturers.api_config.signify.headers');
        $this->cities = config('manufacturers.major_cities');
        $this->fieldMapping = config('manufacturers.field_mapping.signify');
    }

    /**
     * Fetch data from Signify API for multiple cities
     *
     * @return array Raw data from Signify API
     */
    protected function fetchDataFromSource(): array
    {
        Log::info('Fetching Signify data from multiple cities');

        $allResults = [];
        $seenIds = [];

        foreach ($this->cities as $cityData) {
            $city = $cityData['city'];
            $state = $cityData['state'];
            // Construct search display text
            $searchDisplayText = "{$city}, {$state}, USA";

            // Build the full URL with query parameters
            $url = $this->buildUrl($city, $state, $searchDisplayText);

            Log::info("Querying for agents in {$city}, {$state}");

            try {
                $response = Http::withHeaders($this->headers)->get($url);
                $response->throw();
                $data = $response->json();

                // Extract the results
                if (isset($data['ResultList']) && ! empty($data['ResultList'])) {
                    $cityResults = $data['ResultList'];

                    // Add only new records (not seen before)
                    foreach ($cityResults as $result) {
                        if (! in_array($result['Id'], $seenIds)) {
                            $allResults[] = $result;
                            $seenIds[] = $result['Id'];
                        }
                    }

                    Log::info('Found '.count($cityResults)." sales agents in {$city}, {$state} (".
                             count(array_unique(array_column($cityResults, 'Id'))).' unique)');
                } else {
                    Log::info("No results found for {$city}, {$state}");
                }

                // Add a tiny delay in production, but not in tests
                if (!app()->environment('testing')) {
                    usleep(100000); // 0.1 second delay
                }

            } catch (\Exception $e) {
                Log::error("Error fetching data for {$city}, {$state}: ".$e->getMessage());
            }
        }

        if (! empty($allResults)) {
            $totalFound = count($allResults);
            Log::info("Found a total of {$totalFound} unique Signify sales agents across all cities");

            return $allResults;
        } else {
            Log::warning('No Signify sales agents found in any city');

            return [];
        }
    }

    /**
     * Filter the raw data to include only sales agents
     *
     * @param  array  $data  Raw data from source
     * @return array Filtered data
     */
    protected function filterData(array $data): array
    {
        return array_filter($data, function ($result) {
            if (! isset($result['CategoryHierarchy']) || ! is_array($result['CategoryHierarchy'])) {
                return false;
            }

            // Find dealer type in the hierarchy
            for ($i = 0; $i < count($result['CategoryHierarchy']) - 1; $i++) {
                if ($result['CategoryHierarchy'][$i] === 'Dealer Type') {
                    // Only include if it's a sales agent
                    return $result['CategoryHierarchy'][$i + 1] === 'Sales agent';
                }
            }

            return false;
        });
    }

    /**
     * Save response for inspection
     *
     * @param  array  $data  Raw data from source
     */
    protected function saveResponseForInspection(array $data): void
    {
        $outputPath = storage_path('app/signify_response.json');
        File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT));
        Log::info("Response data saved to {$outputPath}");
    }

    /**
     * Build the URL with query parameters
     *
     * @param  string  $city  The city name
     * @param  string  $state  The state code
     * @param  string  $searchDisplayText  The search display text
     * @return string The complete URL
     */
    protected function buildUrl(string $city, string $state, string $searchDisplayText): string
    {
        $parsedUrl = parse_url($this->baseUrl);

        // Parse existing query parameters
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        // Update parameters
        $queryParams['city'] = $city;
        $queryParams['state'] = $state;
        $queryParams['searchDisplayText'] = $searchDisplayText;

        // Reconstruct URL
        $parsedUrl['query'] = http_build_query($queryParams);

        return $this->unparseUrl($parsedUrl);
    }

    /**
     * Reconstruct a URL from its component parts
     *
     * @param  array  $parsedUrl  The array of URL components
     * @return string The reconstructed URL
     */
    protected function unparseUrl(array $parsedUrl): string
    {
        // Start with the scheme and host
        $url = '';
        if (isset($parsedUrl['scheme'])) {
            $url .= $parsedUrl['scheme'].'://';
        }

        if (isset($parsedUrl['host'])) {
            $url .= $parsedUrl['host'];
        }

        // Add port if specified
        if (isset($parsedUrl['port'])) {
            $url .= ':'.$parsedUrl['port'];
        }

        // Add path
        if (isset($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }

        // Add query
        if (isset($parsedUrl['query'])) {
            $url .= '?'.$parsedUrl['query'];
        }

        // Add fragment
        if (isset($parsedUrl['fragment'])) {
            $url .= '#'.$parsedUrl['fragment'];
        }

        return $url;
    }
}
