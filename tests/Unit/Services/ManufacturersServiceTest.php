<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Services\ManufacturersService;
use App\Strategies\ManufacturerStrategy;
use App\Strategies\ManufacturerStrategyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ManufacturersServiceTest extends TestCase
{
    use RefreshDatabase;
    private ManufacturersService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // We'll create the service with a mock factory in each test
    }

    public function test_get_manufacturer_reps_returns_data_from_strategy()
    {
        // Create a mock collection of representatives
        $mockReps = collect([
            [
                'company_name' => 'Test Company 1',
                'manufacturer' => 'Acuity',
                'state' => 'NY',
            ],
            [
                'company_name' => 'Test Company 2',
                'manufacturer' => 'Acuity',
                'state' => 'CA',
            ],
        ]);

        // Create a mock strategy
        $mockStrategy = Mockery::mock(ManufacturerStrategy::class);
        $mockStrategy->shouldReceive('collectReps')
            ->once()
            ->andReturn($mockReps);

        // Create a mock factory
        $mockFactory = Mockery::mock(ManufacturerStrategyFactory::class);
        $mockFactory->shouldReceive('create')
            ->once()
            ->with('acuity')
            ->andReturn($mockStrategy);
            
        // Create the service with our mock factory
        $this->service = new ManufacturersService($mockFactory);

        // Execute the method
        $result = $this->service->getManufacturerReps('acuity');

        // Verify the result
        expect($result)->toBeCollection()
            ->and($result)->toHaveCount(2)
            ->and($result[0]['company_name'])->toBe('Test Company 1')
            ->and($result[1]['company_name'])->toBe('Test Company 2');
    }

    public function test_get_manufacturer_reps_passes_through_invalid_manufacturer_exception()
    {
        // Create a mock factory that throws an InvalidArgumentException
        $mockFactory = Mockery::mock(ManufacturerStrategyFactory::class);
        $mockFactory->shouldReceive('create')
            ->once()
            ->with('invalid')
            ->andThrow(new InvalidArgumentException('Unsupported manufacturer: invalid'));
            
        // Create the service with our mock factory
        $this->service = new ManufacturersService($mockFactory);

        // Execute the method and expect exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported manufacturer: invalid');

        $this->service->getManufacturerReps('invalid');
    }

    public function test_get_manufacturer_reps_wraps_other_exceptions()
    {
        // Create a mock factory that throws a generic exception
        $mockFactory = Mockery::mock(ManufacturerStrategyFactory::class);
        $mockFactory->shouldReceive('create')
            ->once()
            ->with('acuity')
            ->andThrow(new \Exception('API Connection Error'));
            
        // Create the service with our mock factory
        $this->service = new ManufacturersService($mockFactory);

        // Execute the method and expect wrapped exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error collecting representatives: API Connection Error');

        $this->service->getManufacturerReps('acuity');
    }

    public function test_save_representatives_creates_new_companies()
    {
        // Create the service
        $this->service = new ManufacturersService();
        
        // Prepare representative data
        $representatives = collect([
            [
                'company_name' => 'New Company 1',
                'manufacturer' => 'Acuity',
                'state' => 'NY',
                'company_phone' => '555-123-4567',
            ],
            [
                'company_name' => 'New Company 2',
                'manufacturer' => 'Signify',
                'state' => 'CA',
                'company_phone' => '555-987-6543',
            ],
        ]);

        // Execute the method
        $result = $this->service->saveRepresentatives($representatives);

        // Verify new companies were created
        expect($result)->toHaveCount(2);

        // Verify the companies exist in the database
        $this->assertDatabaseHas('companies', [
            'company_name' => 'New Company 1',
            'manufacturer' => 'Acuity',
            'state' => 'NY',
        ]);

        $this->assertDatabaseHas('companies', [
            'company_name' => 'New Company 2',
            'manufacturer' => 'Signify',
            'state' => 'CA',
        ]);
    }

    public function test_save_representatives_updates_existing_companies()
    {
        // Create the service
        $this->service = new ManufacturersService();
        
        // Create an existing company
        $existingCompany = Company::factory()->create([
            'company_name' => 'Existing Company',
            'manufacturer' => 'Cooper',
            'state' => 'TX',
            'company_phone' => '555-111-2222',
        ]);

        // Prepare representative data with updated information
        $representatives = collect([
            [
                'company_name' => 'Existing Company', // Same name
                'manufacturer' => 'Cooper', // Same manufacturer (for matching)
                'state' => 'CA', // Updated state
                'company_phone' => '555-999-8888', // Updated phone
            ],
        ]);

        // Execute the method
        $result = $this->service->saveRepresentatives($representatives);

        // Verify the company was updated
        expect($result)->toHaveCount(1);

        // Verify the company was updated in the database
        $this->assertDatabaseHas('companies', [
            'id' => $existingCompany->id,
            'company_name' => 'Existing Company',
            'manufacturer' => 'Cooper',
            'state' => 'CA', // Should be updated
            'company_phone' => '555-999-8888', // Should be updated
        ]);
    }

    public function test_save_representatives_removes_id_from_data()
    {
        // Create the service
        $this->service = new ManufacturersService();
        
        // Prepare representative data with an ID field that should be removed
        $representatives = collect([
            [
                'id' => 9999, // This should be removed
                'company_name' => 'Company With ID',
                'manufacturer' => 'Acuity',
                'state' => 'WA',
            ],
        ]);

        // Execute the method
        $result = $this->service->saveRepresentatives($representatives);

        // Verify the company was created with a new ID
        expect($result)->toHaveCount(1)
            ->and($result[0]->id)->not->toBe(9999);

        // Verify the company exists in the database with the correct data
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Company With ID',
            'manufacturer' => 'Acuity',
            'state' => 'WA',
        ]);
    }

    public function test_save_representatives_maps_all_fields_correctly()
    {
        // Create the service
        $this->service = new ManufacturersService();
        
        // Prepare representative data with all possible fields
        $representatives = collect([
            [
                'company_name' => 'Full Data Company',
                'manufacturer' => 'Signify',
                'company_phone' => '555-123-4567',
                'address_line_1' => '123 Main St',
                'address_line_2' => 'Suite 100',
                'zip_code' => '12345',
                'city_or_region' => 'Anytown',
                'state' => 'NY',
                'country' => 'USA',
                'website' => 'https://example.com',
                'email' => 'info@example.com',
                'contact_name' => 'John Doe',
                'contact_phone' => '555-987-6543',
            ],
        ]);

        // Execute the method
        $result = $this->service->saveRepresentatives($representatives);

        // Verify the company was created with all fields
        expect($result)->toHaveCount(1);

        // Verify all fields were saved correctly
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Full Data Company',
            'manufacturer' => 'Signify',
            'company_phone' => '555-123-4567',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Suite 100',
            'zip_code' => '12345',
            'city_or_region' => 'Anytown',
            'state' => 'NY',
            'country' => 'USA',
            'website' => 'https://example.com',
            'email' => 'info@example.com',
            'contact_name' => 'John Doe',
            'contact_phone' => '555-987-6543',
        ]);
    }
}
