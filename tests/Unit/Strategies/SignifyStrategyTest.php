<?php

namespace Tests\Unit\Strategies;

use App\Strategies\Manufacturers\SignifyStrategy;
use Illuminate\Support\Facades\Log;

class SignifyStrategyTest extends BaseStrategyTest
{
    private TestSignifyStrategy $strategy;

    private string $testApiUrl = 'https://ws.bullseyelocations.com/RestSearch.svc/DoSearch2';

    protected function setUp(): void
    {
        parent::setUp();

        // Use Log facade spy instead of mocking
        Log::spy();

        // Set up mock configuration
        $this->mockManufacturerConfig('signify', [
            'api_config' => [
                'agents_url' => $this->testApiUrl.'?showAllLocationsPerCountry=true',
                'headers' => ['accept' => 'application/json'],
            ],
            'field_mapping' => [
                'Name' => 'company_name',
                'PhoneNumber' => 'company_phone',
                'Address1' => 'address_line_1',
                'Address2' => 'address_line_2',
                'PostCode' => 'zip_code',
                'City' => 'city_or_region',
                'State' => 'state',
                'CountryName' => 'country',
                'URL' => 'website',
                'EmailAddress' => 'email',
                'ContactName' => 'contact_name',
                'MobileNumber' => 'contact_phone',
            ],
            // Add a small test city list to avoid processing the large list
            'major_cities' => [
                ['city' => 'Test City', 'state' => 'TS'],
            ],
        ]);

        $this->strategy = new TestSignifyStrategy;
    }

    public function test_collect_reps_returns_collection()
    {
        // Create test data
        $testData = [
            [
                'Id' => '1001',
                'Name' => 'Signify Agent 1',
                'PhoneNumber' => '555-123-4567',
                'Address1' => '100 Signify St',
                'City' => 'New York',
                'State' => 'NY',
                'CountryName' => 'USA',
                'URL' => 'https://signify1.example.com',
                'EmailAddress' => 'agent1@signify.example.com',
                'CategoryHierarchy' => ['Dealer Type', 'Sales agent'],
            ],
            [
                'Id' => '1002',
                'Name' => 'Signify Agent 2',
                'PhoneNumber' => '555-987-6543',
                'Address1' => '200 Signify Ave',
                'City' => 'Los Angeles',
                'State' => 'CA',
                'CountryName' => 'USA',
                'URL' => 'https://signify2.example.com',
                'EmailAddress' => 'agent2@signify.example.com',
                'CategoryHierarchy' => ['Dealer Type', 'Sales agent'],
            ],
            // This one should be filtered out (wrong category)
            [
                'Id' => '1003',
                'Name' => 'Signify Distributor',
                'CategoryHierarchy' => ['Distributor'],
            ],
        ];

        // Set the test data to use for this test
        $this->strategy->setTestData($testData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Assertions
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2) // Only the agents with correct CategoryHierarchy
            ->and($result[0])->toHaveKey('manufacturer', 'Signify')
            ->and($result[0])->toHaveKey('company_name', 'Signify Agent 1')
            ->and($result[1])->toHaveKey('company_name', 'Signify Agent 2')
            ->and($result[1])->toHaveKey('state', 'CA');
    }

    public function test_collect_reps_handles_api_error()
    {
        // Create a test strategy with a fetchDataFromSource method that throws an exception
        $errorStrategy = new class extends TestSignifyStrategy
        {
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
        // Create test data with structure from Signify API
        $testData = [
            [
                'Id' => '1234',
                'Name' => 'Test Signify Company',
                'PhoneNumber' => '555-111-2222',
                'Address1' => '789 Test St',
                'Address2' => 'Suite 100',
                'PostCode' => '12345',
                'City' => 'Testville',
                'State' => 'TX',
                'CountryName' => 'USA',
                'URL' => 'https://test.example.com',
                'EmailAddress' => 'info@test.example.com',
                'ContactName' => 'John Doe',
                'MobileNumber' => '555-999-8888',
            ],
        ];

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(SignifyStrategy::class, 'mapToStandardFormat');
        $reflectionMethod->setAccessible(true);

        // Execute the method
        $result = $reflectionMethod->invoke($this->strategy, $testData);

        // Verify field mapping
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Signify')
            ->and($result[0])->toHaveKey('company_name', 'Test Signify Company')
            ->and($result[0])->toHaveKey('company_phone', '555-111-2222')
            ->and($result[0])->toHaveKey('address_line_1', '789 Test St')
            ->and($result[0])->toHaveKey('address_line_2', 'Suite 100')
            ->and($result[0])->toHaveKey('zip_code', '12345')
            ->and($result[0])->toHaveKey('city_or_region', 'Testville')
            ->and($result[0])->toHaveKey('state', 'TX')
            ->and($result[0])->toHaveKey('country', 'USA')
            ->and($result[0])->toHaveKey('website', 'https://test.example.com')
            ->and($result[0])->toHaveKey('email', 'info@test.example.com')
            ->and($result[0])->toHaveKey('contact_name', 'John Doe')
            ->and($result[0])->toHaveKey('contact_phone', '555-999-8888')
            ->and($result[0])->not->toHaveKey('id'); // ID should be removed
    }

    public function test_map_to_standard_format_handles_missing_fields()
    {
        // Test data with minimal fields
        $testData = [
            [
                'Id' => '5678',
                'Name' => 'Minimal Signify Company',
                'State' => 'WA',
                // Missing all other fields
            ],
        ];

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(SignifyStrategy::class, 'mapToStandardFormat');
        $reflectionMethod->setAccessible(true);

        // Execute the method
        $result = $reflectionMethod->invoke($this->strategy, $testData);

        // Verify handling of missing fields
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Signify')
            ->and($result[0])->toHaveKey('company_name', 'Minimal Signify Company')
            ->and($result[0])->toHaveKey('state', 'WA')
            ->and($result[0])->toHaveKey('country', 'USA') // Default country
            ->and($result[0])->not->toHaveKey('company_phone')
            ->and($result[0])->not->toHaveKey('address_line_1')
            ->and($result[0])->not->toHaveKey('email');
    }

    public function test_filter_agents_only_includes_sales_agencies()
    {
        // Test data with different categories
        $testData = [
            // Should include (has 'Dealer Type' -> 'Sales agent')
            [
                'Id' => '1001',
                'Name' => 'Signify Sales Agency',
                'CategoryHierarchy' => ['Dealer Type', 'Sales agent'],
            ],
            // Should include (has 'Dealer Type' -> 'Sales agent')
            [
                'Id' => '1002',
                'Name' => 'Another Signify Agency',
                'CategoryHierarchy' => ['Dealer Type', 'Sales agent'],
            ],
            // Should exclude (not an agent)
            [
                'Id' => '1003',
                'Name' => 'Signify Distributor',
                'CategoryHierarchy' => ['Dealer Type', 'Distributor'],
            ],
            // Should exclude (no CategoryHierarchy)
            [
                'Id' => '1004',
                'Name' => 'No Category Company',
            ],
            // Should exclude (empty CategoryHierarchy)
            [
                'Id' => '1005',
                'Name' => 'Empty Category Company',
                'CategoryHierarchy' => [],
            ],
        ];

        // Test the filterData method directly
        $filteredData = $this->strategy->publicFilterData($testData);

        // Verify that only sales agents are included
        expect($filteredData)->toBeArray()
            ->and($filteredData)->toHaveCount(2)
            ->and($filteredData[0]['Name'])->toBe('Signify Sales Agency')
            ->and($filteredData[1]['Name'])->toBe('Another Signify Agency');

        // Now test through the collectReps method
        $this->strategy->setTestData($testData);
        $result = $this->strategy->collectReps();

        // Verify that only sales agencies are included in the final collection
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('company_name', 'Signify Sales Agency')
            ->and($result[1])->toHaveKey('company_name', 'Another Signify Agency');
    }
}
