<?php

namespace App\Strategies\Manufacturers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CooperStrategy extends BaseManufacturerStrategy
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
        $this->manufacturerName = 'Cooper';
        $this->baseUrl = config('manufacturers.api_config.cooper.agents_url');
        $this->headers = config('manufacturers.api_config.cooper.headers');
        $this->cities = config('manufacturers.major_cities');
        $this->fieldMapping = config('manufacturers.field_mapping.cooper');
    }

    /**
     * Fetch data from Cooper API for multiple cities
     *
     * @return array Raw data from Cooper API
     */
    protected function fetchDataFromSource(): array
    {
        Log::info('Fetching Cooper data from multiple cities');

        $allResults = [];
        $seenIds = [];

        foreach ($this->cities as $cityData) {
            $city = $cityData['city'];
            $state = $cityData['state'];

            // Construct search display text
            $searchDisplayText = "{$city}, {$state}, USA";

            // Build the full URL with query parameters
            $url = $this->buildUrl($city, $state, $searchDisplayText);

            Log::info("Querying Cooper agents in {$city}, {$state}");

            try {
                $response = Http::withHeaders($this->headers)->get($url);
                $response->throw();
                $data = $response->json();

                // Extract the results
                if (isset($data['ResultList']) && ! empty($data['ResultList'])) {
                    $cityResults = $data['ResultList'];

                    // Save the full response for inspection

                    // Add only new records (not seen before)
                    foreach ($cityResults as $result) {
                        if (! in_array($result['Id'], $seenIds)) {
                            $allResults[] = $result;
                            $seenIds[] = $result['Id'];
                        }
                    }
                    Log::info('Found '.count($cityResults)." Cooper agents in {$city}, {$state} (".
                            count(array_unique(array_column($cityResults, 'Id'))).' unique)');

                    // Check total count reported by API
                    if (isset($data['TotalResults']) && $data['TotalResults'] > 0) {
                        $totalCount = $data['TotalResults'];
                        Log::info("Total locations according to API for {$city}, {$state}: {$totalCount}");
                    }
                } else {
                    Log::info("No Cooper results found for {$city}, {$state}");
                }

                // Add a small delay to avoid overwhelming the API
                usleep(1000000); // 1 second delay

            } catch (\Exception $e) {
                Log::error("Error fetching Cooper data for {$city}, {$state}: ".$e->getMessage());
            }
        }

        if (! empty($allResults)) {
            $totalFound = count($allResults);
            Log::info("Found a total of {$totalFound} unique Cooper agents across all cities");

            return $allResults;
        } else {
            Log::warning('No Cooper agents found in any city');

            return [];
        }
    }

    /**
     * Filter the raw data to include only agents
     *
     * @param  array  $data  Raw data from source
     * @return array Filtered data
     */
    protected function filterData(array $data): array
    {
        return collect($data)->filter(function ($result) {
            if (! isset($result['CategoryHierarchy']) || ! is_array($result['CategoryHierarchy'])) {
                return false;
            }

            // For Cooper, we want to include agents (checking for "Agent Type" category)
            if (count($result['CategoryHierarchy']) >= 1) {
                return $result['CategoryHierarchy'][0] === 'Agent Type';
            }

            return false;
        })->toArray();
    }

    /**
     * Save response for inspection
     *
     * @param  array  $data  Raw data from source
     */
    protected function saveResponseForInspection(array $data): void
    {
        $outputPath = storage_path('app/cooper_response.json');
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
        $queryParams['radius'] = '200';
        $queryParams['searchTypeId'] = '1';
        $queryParams['categoryIDs'] = '';
        $queryParams['showAllLocationsPerCountry'] = 'true';

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
