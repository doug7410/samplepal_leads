<?php

namespace App\Strategies\Manufacturers;

use App\Strategies\ManufacturerStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class BaseManufacturerStrategy implements ManufacturerStrategy
{
    /**
     * The manufacturer name
     */
    protected string $manufacturerName;

    /**
     * Base URL for API
     */
    protected string $baseUrl;

    /**
     * API request headers
     */
    protected array $headers;

    /**
     * Field mapping for data
     */
    protected array $fieldMapping;

    /**
     * Collect representatives from the manufacturer's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection
    {
        try {
            Log::info("Fetching {$this->manufacturerName} data from API");

            // Fetch data from source (API, file, etc)
            $data = $this->fetchDataFromSource();

            // Save response for inspection if needed
            $this->saveResponseForInspection($data);

            // Filter the data if needed
            $filteredData = $this->filterData($data);

            // Map to standard format
            $standardized = $this->mapToStandardFormat($filteredData);

            Log::info("Processed {$standardized->count()} {$this->manufacturerName} representatives");

            return $standardized;
        } catch (\Exception $e) {
            Log::error("Error fetching {$this->manufacturerName} data: ".$e->getMessage());

            return collect([]);
        }
    }

    /**
     * Fetch data from source (to be implemented by concrete strategies)
     *
     * @return array Raw data from source
     */
    abstract protected function fetchDataFromSource(): array;

    /**
     * Filter the raw data (can be overridden by concrete strategies)
     *
     * @param  array  $data  Raw data from source
     * @return array Filtered data
     */
    protected function filterData(array $data): array
    {
        return $data;
    }

    /**
     * Save response for inspection (can be overridden by concrete strategies)
     *
     * @param  array  $data  Raw data from source
     */
    protected function saveResponseForInspection(array $data): void
    {
        // Default implementation does nothing, can be overridden
    }

    /**
     * Map the API data to our standardized format
     *
     * @param  array  $data  The raw data
     * @return Collection The standardized data
     */
    protected function mapToStandardFormat(array $data): Collection
    {
        return collect($data)->map(function ($item) {
            // Start with the manufacturer name
            $mappedData = [
                'manufacturer' => $this->manufacturerName,
            ];

            // Map fields based on the configuration
            foreach ($this->fieldMapping as $sourceField => $targetField) {
                if (isset($item[$sourceField]) && ! empty($item[$sourceField])) {
                    $mappedData[$targetField] = $item[$sourceField];
                }
            }

            // Handle missing fields
            if (! isset($mappedData['country'])) {
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
