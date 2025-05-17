<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CompaniesPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/companies';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Companies');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@companies-heading' => 'h1',
            '@companies-table' => '.companies-table',
            '@import-button' => '[data-testid="import-button"]',
            '@filter-button' => '[data-testid="filter-button"]',
            '@search-input' => 'input[type="search"]',
        ];
    }

    /**
     * Search for a company.
     *
     * @param Browser $browser
     * @param string $query
     * @return Browser
     */
    public function searchCompany(Browser $browser, string $query): Browser
    {
        return $browser->type('@search-input', $query)
            ->pause(500); // Allow time for search to process
    }
}