<?php

namespace Tests\Unit\Strategies;

use App\Strategies\Manufacturers\CooperStrategy;

/**
 * A test adapter for CooperStrategy that allows us to override protected methods for testing
 */
class TestCooperStrategy extends CooperStrategy
{
    /**
     * Test-specific data to return from fetchDataFromSource
     */
    private array $testData = [];

    /**
     * Set the test data to use during tests
     */
    public function setTestData(array $data): void
    {
        $this->testData = $data;
    }

    /**
     * Override fetchDataFromSource to return test data instead of making HTTP calls
     */
    protected function fetchDataFromSource(): array
    {
        // Return the test data without making any HTTP calls
        return $this->testData;
    }

    /**
     * Make the protected filterData method public for direct testing
     */
    public function publicFilterData(array $data): array
    {
        return $this->filterData($data);
    }
}
