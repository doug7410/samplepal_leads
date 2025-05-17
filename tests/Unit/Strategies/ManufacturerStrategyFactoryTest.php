<?php

use App\Strategies\Manufacturers\AcuityStrategy;
use App\Strategies\Manufacturers\CooperStrategy;
use App\Strategies\Manufacturers\SignifyStrategy;
use App\Strategies\ManufacturerStrategyFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManufacturerStrategyFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock HTTP facade to prevent any real API calls
        Http::preventStrayRequests();
        Http::fake();
        
        // Mock all necessary manufacturer configurations with minimal data
        Config::set('manufacturers.major_cities', [
            ['city' => 'Test City', 'state' => 'TS']
        ]);
        
        // Fake API URLs and configurations
        Config::set('manufacturers.api_config.signify.agents_url', 'https://example.com/test');
        Config::set('manufacturers.api_config.signify.headers', []);
        Config::set('manufacturers.field_mapping.signify', []);
        
        Config::set('manufacturers.api_config.cooper.agents_url', 'https://example.com/test');
        Config::set('manufacturers.api_config.cooper.headers', []);
        Config::set('manufacturers.field_mapping.cooper', []);
        
        Config::set('manufacturers.api_config.acuity.agents_url', 'https://example.com/test');
        Config::set('manufacturers.api_config.acuity.headers', []);
        Config::set('manufacturers.field_mapping.acuity', []);
    }

    public function test_factory_creates_correct_strategy_for_signify()
    {
        $factory = new ManufacturerStrategyFactory();
        $strategy = $factory->create('signify');
        expect($strategy)->toBeInstanceOf(SignifyStrategy::class);
    }

    public function test_factory_creates_correct_strategy_for_cooper()
    {
        $factory = new ManufacturerStrategyFactory();
        $strategy = $factory->create('cooper');
        expect($strategy)->toBeInstanceOf(CooperStrategy::class);
    }

    public function test_factory_creates_correct_strategy_for_acuity()
    {
        $factory = new ManufacturerStrategyFactory();
        $strategy = $factory->create('acuity');
        expect($strategy)->toBeInstanceOf(AcuityStrategy::class);
    }

    public function test_factory_handles_case_insensitive_manufacturer_names()
    {
        $factory = new ManufacturerStrategyFactory();
        $strategy1 = $factory->create('Signify');
        $strategy2 = $factory->create('COOPER');
        $strategy3 = $factory->create('AcUiTy');

        expect($strategy1)->toBeInstanceOf(SignifyStrategy::class)
            ->and($strategy2)->toBeInstanceOf(CooperStrategy::class)
            ->and($strategy3)->toBeInstanceOf(AcuityStrategy::class);
    }

    public function test_factory_throws_exception_for_invalid_manufacturer()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported manufacturer: invalid');

        $factory = new ManufacturerStrategyFactory();
        $factory->create('invalid');
    }
    
    // We're removing the static compatibility test since we've moved away from static methods
    public function test_factory_facade_can_be_created_and_used()
    {
        // Create a factory facade/wrapper that can be used statically if needed
        $factoryFacade = new class {
            public static function create(string $manufacturer) {
                $factory = new ManufacturerStrategyFactory();
                return $factory->create($manufacturer);
            }
        };
        
        $strategy = $factoryFacade::create('signify');
        expect($strategy)->toBeInstanceOf(SignifyStrategy::class);
    }
}