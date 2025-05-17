<?php

namespace Tests\Unit\Strategies;

use App\Strategies\Manufacturers\AcuityStrategy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcuityStrategyTest extends BaseStrategyTest
{
    private TestAcuityStrategy $strategy;

    private string $testApiUrl = 'https://www.acuitybrands.com/api/howtobuy/getfromsource/agents';

    protected function setUp(): void
    {
        parent::setUp();

        // Use Log facade spy instead of mocking
        Log::spy();

        // Set up mock configuration
        $this->mockManufacturerConfig('acuity', [
            'api_config' => [
                'agents_url' => $this->testApiUrl,
                'headers' => ['accept' => 'application/json'],
            ],
            'field_mapping' => [
                'Business' => 'company_name',
                'Phone' => 'company_phone',
                'AddressLine1' => 'address_line_1',
                'AddressLine2' => 'address_line_2',
                'PostalCode' => 'zip_code',
                'Locality' => 'city_or_region',
                'AdminDistrict' => 'state',
                'CountryRegion' => 'country',
                'Web' => 'website',
            ],
        ]);

        $this->strategy = new TestAcuityStrategy;
    }

    public function test_collect_reps_returns_collection()
    {
        // Create a small mock response with just a few items
        $mockData = [
            [
                'Business' => 'Acme Lighting',
                'Phone' => '555-123-4567',
                'AddressLine1' => '123 Main St',
                'Locality' => 'Anytown',
                'AdminDistrict' => 'CA',
                'CountryRegion' => 'USA',
                'Web' => 'https://acmelighting.example.com',
            ],
            [
                'Business' => 'Bright Solutions',
                'Phone' => '555-987-6543',
                'AddressLine1' => '456 Oak Ave',
                'Locality' => 'Somewhere',
                'AdminDistrict' => 'NY',
                'CountryRegion' => 'USA',
                'Web' => 'https://brightsolutions.example.com',
            ],
        ];

        // Set the test data instead of mocking HTTP
        $this->strategy->setTestData($mockData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Assertions
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('company_name', 'Acme Lighting')
            ->and($result[0])->toHaveKey('manufacturer', 'Acuity')
            ->and($result[1])->toHaveKey('company_name', 'Bright Solutions')
            ->and($result[1])->toHaveKey('state', 'NY');
    }

    public function test_collect_reps_handles_api_error()
    {
        // Create a test strategy with a fetchDataFromSource method that throws an exception
        $errorStrategy = new class extends TestAcuityStrategy {
            protected function fetchDataFromSource(): array
            {
                throw new \Exception('Test API error');
            }
        };

        // Execute the method
        $result = $errorStrategy->collectReps();

        // Should return empty collection on error
        expect($result)->toBeCollection()
            ->and($result)->toBeEmpty();
    }

    public function test_map_to_standard_format_correctly_maps_fields()
    {
        // Test data with all fields present
        $mockData = [
            [
                'Business' => 'Test Company',
                'Phone' => '555-111-2222',
                'AddressLine1' => '789 Test St',
                'AddressLine2' => 'Suite 100',
                'PostalCode' => '12345',
                'Locality' => 'Testville',
                'AdminDistrict' => 'TX',
                'CountryRegion' => 'USA',
                'Web' => 'https://test.example.com',
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($mockData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Verify all fields were mapped correctly
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Acuity')
            ->and($result[0])->toHaveKey('company_name', 'Test Company')
            ->and($result[0])->toHaveKey('company_phone', '555-111-2222')
            ->and($result[0])->toHaveKey('address_line_1', '789 Test St')
            ->and($result[0])->toHaveKey('address_line_2', 'Suite 100')
            ->and($result[0])->toHaveKey('zip_code', '12345')
            ->and($result[0])->toHaveKey('city_or_region', 'Testville')
            ->and($result[0])->toHaveKey('state', 'TX')
            ->and($result[0])->toHaveKey('country', 'USA')
            ->and($result[0])->toHaveKey('website', 'https://test.example.com');
    }

    public function test_map_to_standard_format_handles_missing_fields()
    {
        // Test data with missing fields
        $mockData = [
            [
                'Business' => 'Minimal Company',
                'AdminDistrict' => 'CA',
                // Missing other fields
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($mockData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Verify mapping with missing fields
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Acuity')
            ->and($result[0])->toHaveKey('company_name', 'Minimal Company')
            ->and($result[0])->toHaveKey('state', 'CA')
            ->and($result[0])->toHaveKey('country', 'USA') // Default value
            ->and($result[0])->not->toHaveKey('company_phone')
            ->and($result[0])->not->toHaveKey('address_line_1');
    }

    public function test_id_field_is_removed_if_present()
    {
        // Test data with an ID field that should be removed
        $mockData = [
            [
                'Business' => 'ID Test Company',
                'id' => 12345, // This should be removed
                'AdminDistrict' => 'WA',
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($mockData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Verify ID field was removed
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Acuity')
            ->and($result[0])->toHaveKey('company_name', 'ID Test Company')
            ->and($result[0])->not->toHaveKey('id');
    }
}