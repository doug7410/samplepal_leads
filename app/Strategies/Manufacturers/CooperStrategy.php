<?php

namespace App\Strategies\Manufacturers;

use App\Strategies\ManufacturerStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class CooperStrategy implements ManufacturerStrategy
{
    /**
     * Base URL for Cooper API
     */
    protected string $baseUrl;

    /**
     * API request headers
     */
    protected array $headers;

    /**
     * List of major cities to search
     */
    protected array $cities;

    /**
     * Field mapping for Cooper data
     */
    protected array $fieldMapping;

    /**
     * Constructor to initialize class properties
     */
    public function __construct()
    {
        $this->baseUrl = config('manufacturers.api_config.cooper.agents_url');
        $this->headers = config('manufacturers.api_config.cooper.headers');
        $this->cities = config('manufacturers.major_cities');
        $this->fieldMapping = config('manufacturers.field_mapping.cooper');
    }

    /**
     * Collect representatives from Cooper's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection
    {
        Log::info('Fetching Cooper data from multiple cities');

        $allResults = [];
        $seenIds = [];

        foreach ($this->cities as $cityData) {
            $city = $cityData['city'];
            $state = $cityData['state'];
            dump($city);

            // Construct search display text
            $searchDisplayText = "{$city}, {$state}, USA";

            // Build the full URL with query parameters
            $url = $this->buildUrl($city, $state, $searchDisplayText);

            Log::info("Querying Cooper agents in {$city}, {$state}");

            try {
                $response = Http::withHeaders($this->headers)->get($url);
                $response->throw();
                $data = $response->json();

                // Write data to file for inspection
                $outputPath = storage_path('app/cooper_response.json');
                File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT));
                Log::info("Response data saved to {$outputPath}");

                // Extract the results
                if (isset($data['ResultList']) && !empty($data['ResultList'])) {
                    $cityResults = $data['ResultList'];

                    // Save the full response for inspection
                    dump("Found " . count($cityResults) . " total Cooper results before filtering");

                    // Cooper uses a different category structure than Signify
                    $cityResults = collect($cityResults)->filter(function($result) {
                        if (!isset($result['CategoryHierarchy']) || !is_array($result['CategoryHierarchy'])) {
                            return false;
                        }

                        // For Cooper, we want to include agents (checking for "Agent Type" category)
                        if (count($result['CategoryHierarchy']) >= 1) {
                            return $result['CategoryHierarchy'][0] === 'Agent Type';
                        }

                        return false;
                    })->toArray();

                    // Add only new records (not seen before)
                    foreach ($cityResults as $result) {
                        if (!in_array($result['Id'], $seenIds)) {
                            $allResults[] = $result;
                            $seenIds[] = $result['Id'];
                        }
                    }
                    dump("Found " . count($cityResults) . " Cooper agents in {$city}, {$state} (" .
                        count(array_unique(array_column($cityResults, 'Id'))) . " unique)");
                    Log::info("Found " . count($cityResults) . " Cooper agents in {$city}, {$state} (" .
                             count(array_unique(array_column($cityResults, 'Id'))) . " unique)");

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
                Log::error("Error fetching Cooper data for {$city}, {$state}: " . $e->getMessage());
            }
        }

        if (!empty($allResults)) {
            $totalFound = count($allResults);
            Log::info("Found a total of {$totalFound} unique Cooper agents across all cities");
            // Map the data to our standardized format
            return $this->mapToStandardFormat($allResults);
        } else {
            Log::warning("No Cooper agents found in any city");
            return collect([]);
        }
    }

    /**
     * Build the URL with query parameters
     *
     * @param string $city The city name
     * @param string $state The state code
     * @param string $searchDisplayText The search display text
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
     * @param array $parsedUrl The array of URL components
     * @return string The reconstructed URL
     */
    protected function unparseUrl(array $parsedUrl): string
    {
        // Start with the scheme and host
        $url = '';
        if (isset($parsedUrl['scheme'])) {
            $url .= $parsedUrl['scheme'] . '://';
        }

        if (isset($parsedUrl['host'])) {
            $url .= $parsedUrl['host'];
        }

        // Add port if specified
        if (isset($parsedUrl['port'])) {
            $url .= ':' . $parsedUrl['port'];
        }

        // Add path
        if (isset($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }

        // Add query
        if (isset($parsedUrl['query'])) {
            $url .= '?' . $parsedUrl['query'];
        }

        // Add fragment
        if (isset($parsedUrl['fragment'])) {
            $url .= '#' . $parsedUrl['fragment'];
        }

        return $url;
    }

    /**
     * Map the Cooper API data to our standardized format
     *
     * @param array $data The raw data from the Cooper API
     * @return Collection The standardized data
     */
    protected function mapToStandardFormat(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            // Start with the manufacturer name
            $mappedData = [
                'manufacturer' => 'Cooper',
            ];

            // Map fields based on the configuration
            foreach ($this->fieldMapping as $sourceField => $targetField) {
                if (isset($item[$sourceField])) {
                    $mappedData[$targetField] = $item[$sourceField];
                }
            }

            // Handle missing fields
            if (!isset($mappedData['country'])) {
                $mappedData['country'] = 'USA';
            }

            // Ensure we don't pass any ID field - let the database generate it
            if (isset($mappedData['id'])) {
                unset($mappedData['id']);
            }

            return $mappedData;
        });
    }
}
