<?php

namespace App\Strategies\Manufacturers;

use App\Strategies\ManufacturerStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AcuityStrategy implements ManufacturerStrategy
{
    /**
     * Base URL for Acuity API
     */
    protected string $baseUrl;

    /**
     * API request headers
     */
    protected array $headers;

    /**
     * Field mapping for Acuity data
     */
    protected array $fieldMapping;

    /**
     * Constructor to initialize class properties
     */
    public function __construct()
    {
        $this->baseUrl = config('manufacturers.api_config.acuity.agents_url');
        $this->headers = config('manufacturers.api_config.acuity.headers');
        $this->fieldMapping = config('manufacturers.field_mapping.acuity');
    }

    /**
     * Collect representatives from Acuity's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection
    {
        Log::info('Fetching Acuity data from API');
        Log::info("Fetching data from: {$this->baseUrl}");

        try {
            $response = Http::withHeaders($this->headers)->get($this->baseUrl);
            $response->throw();
            $data = $response->json();

            // Write data to file for inspection
            $outputPath = storage_path('app/acuity_response.json');
            File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT));
            Log::info("Response data saved to {$outputPath}");

            $totalRecords = count($data);
            Log::info("Received data with {$totalRecords} records");
            dump("Received {$totalRecords} Acuity agent records");

            // Map the data to our standardized format
            return $this->mapToStandardFormat($data);
        } catch (\Exception $e) {
            Log::error("Error fetching Acuity data: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Map the Acuity API data to our standardized format
     *
     * @param array $data The raw data from the Acuity API
     * @return Collection The standardized data
     */
    protected function mapToStandardFormat(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            // Start with the manufacturer name
            $mappedData = [
                'manufacturer' => 'Acuity',
            ];

            // Map fields based on the configuration
            foreach ($this->fieldMapping as $sourceField => $targetField) {
                if (isset($item[$sourceField]) && !empty($item[$sourceField])) {
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