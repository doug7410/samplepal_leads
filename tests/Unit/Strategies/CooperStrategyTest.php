<?php

namespace Tests\Unit\Strategies;

use App\Strategies\Manufacturers\CooperStrategy;
use Illuminate\Support\Facades\Log;

class CooperStrategyTest extends BaseStrategyTest
{
    private TestCooperStrategy $strategy;

    private string $testApiUrl = 'https://ws.bullseyelocations.com/RestSearch.svc/DoSearch2';

    protected function setUp(): void
    {
        parent::setUp();

        // Use Log facade spy instead of mocking
        Log::spy();

        // Set up mock configuration with limited number of cities for testing
        $this->mockManufacturerConfig('cooper', [
            'api_config' => [
                'agents_url' => $this->testApiUrl.'?countryId=1',
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
            'major_cities' => [
                ['city' => 'New York', 'state' => 'NY'],
                ['city' => 'Los Angeles', 'state' => 'CA'],
            ],
        ]);

        $this->strategy = new TestCooperStrategy;
    }

    public function test_collect_reps_returns_collection()
    {
        // Create a mock response with the structure expected from Cooper API
        $mockData = [
            [
                'Id' => '1001',
                'Name' => 'Cooper Agent 1',
                'PhoneNumber' => '555-123-4567',
                'Address1' => '100 Cooper St',
                'City' => 'New York',
                'State' => 'NY',
                'CountryName' => 'USA',
                'URL' => 'https://cooper1.example.com',
                'EmailAddress' => 'agent1@cooper.example.com',
                'CategoryHierarchy' => ['Agent Type', 'Lighting'],
            ],
            [
                'Id' => '1002',
                'Name' => 'Cooper Agent 2',
                'PhoneNumber' => '555-987-6543',
                'Address1' => '200 Cooper Ave',
                'City' => 'Los Angeles',
                'State' => 'CA',
                'CountryName' => 'USA',
                'URL' => 'https://cooper2.example.com',
                'EmailAddress' => 'agent2@cooper.example.com',
                'CategoryHierarchy' => ['Agent Type', 'Lighting'],
            ],
            // This one should be filtered out as it's not an agent
            [
                'Id' => '1003',
                'Name' => 'Cooper Distributor',
                'CategoryHierarchy' => ['Distributor Type'],
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($mockData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Assertions
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2) // Only the agents with correct CategoryHierarchy
            ->and($result[0])->toHaveKey('manufacturer', 'Cooper')
            ->and($result[0])->toHaveKey('company_name', 'Cooper Agent 1')
            ->and($result[1])->toHaveKey('company_name', 'Cooper Agent 2')
            ->and($result[1])->toHaveKey('state', 'CA');
    }

    public function test_collect_reps_handles_api_error()
    {
        // Create a test strategy with a fetchDataFromSource method that throws an exception
        $errorStrategy = new class extends TestCooperStrategy
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

    public function test_build_url_formats_correctly()
    {
        // We'll need to use reflection to test this protected method
        $reflectionMethod = new \ReflectionMethod(CooperStrategy::class, 'buildUrl');
        $reflectionMethod->setAccessible(true);

        // Execute the method
        $url = $reflectionMethod->invoke($this->strategy, 'Boston', 'MA', 'Boston, MA, USA');

        // Check if the URL has expected query parameters
        expect($url)->toContain('city=Boston')
            ->and($url)->toContain('state=MA')
            ->and($url)->toContain('searchDisplayText=Boston%2C+MA%2C+USA')
            ->and($url)->toContain('radius=200');
    }

    public function test_unparse_url_reconstructs_url_correctly()
    {
        // Use reflection to test this protected method
        $reflectionMethod = new \ReflectionMethod(CooperStrategy::class, 'unparseUrl');
        $reflectionMethod->setAccessible(true);

        // Create parsed URL parts
        $parsedUrl = [
            'scheme' => 'https',
            'host' => 'example.com',
            'path' => '/api/search',
            'query' => 'param1=value1&param2=value2',
            'fragment' => 'section1',
        ];

        // Execute the method
        $url = $reflectionMethod->invoke($this->strategy, $parsedUrl);

        // Check if the URL is reconstructed correctly
        expect($url)->toBe('https://example.com/api/search?param1=value1&param2=value2#section1');
    }

    public function test_map_to_standard_format_correctly_maps_fields()
    {
        // Create test data with structure from Cooper API
        $testData = [
            [
                'Id' => '1234',
                'Name' => 'Test Cooper Company',
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
                'CategoryHierarchy' => ['Agent Type'],
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($testData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Verify field mapping
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Cooper')
            ->and($result[0])->toHaveKey('company_name', 'Test Cooper Company')
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
                'Name' => 'Minimal Cooper Company',
                'State' => 'WA',
                'CategoryHierarchy' => ['Agent Type'],
                // Missing all other fields
            ],
        ];

        // Set the test data
        $this->strategy->setTestData($testData);

        // Execute the method
        $result = $this->strategy->collectReps();

        // Verify handling of missing fields
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toHaveKey('manufacturer', 'Cooper')
            ->and($result[0])->toHaveKey('company_name', 'Minimal Cooper Company')
            ->and($result[0])->toHaveKey('state', 'WA')
            ->and($result[0])->toHaveKey('country', 'USA') // Default country
            ->and($result[0])->not->toHaveKey('company_phone')
            ->and($result[0])->not->toHaveKey('address_line_1')
            ->and($result[0])->not->toHaveKey('email');
    }

    public function test_filter_data_only_includes_agents()
    {
        // Test data with different categories
        $testData = [
            // Should include (has 'Agent Type')
            [
                'Id' => '1001',
                'Name' => 'Cooper Agent 1',
                'CategoryHierarchy' => ['Agent Type'],
            ],
            // Should include (has 'Agent Type')
            [
                'Id' => '1002',
                'Name' => 'Cooper Agent 2',
                'CategoryHierarchy' => ['Agent Type', 'Lighting'],
            ],
            // Should exclude (not an agent)
            [
                'Id' => '1003',
                'Name' => 'Cooper Distributor',
                'CategoryHierarchy' => ['Distributor Type'],
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

        // Verify that only agents are included
        expect($filteredData)->toBeArray()
            ->and($filteredData)->toHaveCount(2)
            ->and($filteredData[0]['Name'])->toBe('Cooper Agent 1')
            ->and($filteredData[1]['Name'])->toBe('Cooper Agent 2');

        // Now test through the collectReps method
        $this->strategy->setTestData($testData);
        $result = $this->strategy->collectReps();

        // Verify that only agents are included in the final collection
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('company_name', 'Cooper Agent 1')
            ->and($result[1])->toHaveKey('company_name', 'Cooper Agent 2');
    }
}
