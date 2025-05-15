<?php

namespace App\Strategies\Manufacturers;

use App\Strategies\ManufacturerStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class SignifyStrategy implements ManufacturerStrategy
{
    /**
     * Base URL for Signify API
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
     * Field mapping for Signify data
     */
    protected array $fieldMapping;

    /**
     * Constructor to initialize class properties
     */
    public function __construct()
    {
        $this->baseUrl = config('manufacturers.api_config.signify.agents_url');
        $this->headers = config('manufacturers.api_config.signify.headers');
        $this->cities = config('manufacturers.major_cities');
        $this->fieldMapping = config('manufacturers.field_mapping.signify');
    }

    /**
     * Collect representatives from Signify's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection
    {
        Log::info('Fetching Signify data from multiple cities');

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

            Log::info("Querying for agents in {$city}, {$state}");

            try {
                $response = Http::withHeaders($this->headers)->get($url);
                $response->throw();
                $data = $response->json();

                // Write data to file for inspection
                $outputPath = storage_path('app/signify_response.json');
                File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT));
                Log::info("Response data saved to {$outputPath}");

                // Extract the results
                if (isset($data['ResultList']) && !empty($data['ResultList'])) {
                    $cityResults = $data['ResultList'];

                    // Filter to include only sales agents and exclude distributors
                    $cityResults = array_filter($cityResults, function($result) {
                        if (!isset($result['CategoryHierarchy']) || !is_array($result['CategoryHierarchy'])) {
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

                    // Add only new records (not seen before)
                    foreach ($cityResults as $result) {
                        if (!in_array($result['Id'], $seenIds)) {
                            $allResults[] = $result;
                            $seenIds[] = $result['Id'];
                        }
                    }

                    Log::info("Found " . count($cityResults) . " sales agents in {$city}, {$state} (" .
                             count(array_unique(array_column($cityResults, 'Id'))) . " unique)");
                } else {
                    Log::info("No results found for {$city}, {$state}");
                }

                // Add a small delay to avoid overwhelming the API
                usleep(1000000); // 1 second delay

            } catch (\Exception $e) {
                Log::error("Error fetching data for {$city}, {$state}: " . $e->getMessage());
            }
        }

        if (!empty($allResults)) {
            $totalFound = count($allResults);
            Log::info("Found a total of {$totalFound} unique Signify sales agents across all cities");
            // Map the data to our standardized format
            return $this->mapToStandardFormat($allResults);
        } else {
            Log::warning("No Signify sales agents found in any city");
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
     * Map the Signify API data to our standardized format
     *
     * @param array $data The raw data from the Signify API
     * @return Collection The standardized data
     */
    protected function mapToStandardFormat(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            // Start with the manufacturer name
            $mappedData = [
                'manufacturer' => 'Signify',
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
