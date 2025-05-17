<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * The Mockery context for this test.
     *
     * @var \Mockery\Container|null
     */
    protected $mockeryContainer;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a new Mockery container for each test
        $this->mockeryContainer = Mockery::getContainer();
        if (is_null($this->mockeryContainer)) {
            $this->mockeryContainer = new \Mockery\Container;
            Mockery::setContainer($this->mockeryContainer);
        }
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * NOTE: This resolves Mockery issues with Laravel facades and final classes.
     * If you encounter "Cannot redeclare Mockery_X::shouldReceive()" errors,
     * ensure this cleanup method is properly configured.
     */
    protected function tearDown(): void
    {
        // Close mockery expectations
        if ($this->mockeryContainer) {
            $this->mockeryContainer->mockery_close();
            Mockery::resetContainer();
        }

        // Ensure all facades are cleared
        Facade::clearResolvedInstances();

        parent::tearDown();

        // Added extra insurance - global clean
        Mockery::close();
    }
}
