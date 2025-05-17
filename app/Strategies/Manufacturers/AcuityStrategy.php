<?php

namespace App\Strategies\Manufacturers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcuityStrategy extends BaseManufacturerStrategy
{
    /**
     * Constructor to initialize class properties
     */
    public function __construct()
    {
        $this->manufacturerName = 'Acuity';
        $this->baseUrl = config('manufacturers.api_config.acuity.agents_url');
        $this->headers = config('manufacturers.api_config.acuity.headers');
        $this->fieldMapping = config('manufacturers.field_mapping.acuity');
    }

    /**
     * Fetch data from Acuity API
     *
     * @return array Raw data from Acuity API
     */
    protected function fetchDataFromSource(): array
    {
        Log::info("Fetching data from: {$this->baseUrl}");

        $response = Http::withHeaders($this->headers)->get($this->baseUrl);
        $response->throw();

        return $response->json();
    }

    /**
     * Save response for inspection
     *
     * @param  array  $data  Raw data from source
     */
    protected function saveResponseForInspection(array $data): void
    {
        $outputPath = storage_path('app/acuity_response.json');
        File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT));
        Log::info("Response data saved to {$outputPath}");

        $totalRecords = count($data);
        Log::info("Received data with {$totalRecords} records");
    }
}
