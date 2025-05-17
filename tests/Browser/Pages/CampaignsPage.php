<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CampaignsPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/campaigns';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Campaigns');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@campaigns-heading' => 'h1',
            '@campaigns-table' => '.campaigns-table',
            '@create-campaign-button' => '[data-testid="create-campaign-button"]',
            '@filter-button' => '[data-testid="filter-button"]',
            '@search-input' => 'input[type="search"]',
        ];
    }

    /**
     * Click the create campaign button.
     *
     * @param Browser $browser
     * @return Browser
     */
    public function createCampaign(Browser $browser): Browser
    {
        return $browser->click('@create-campaign-button')
            ->waitForLocation('/campaigns/create');
    }

    /**
     * Search for a campaign.
     *
     * @param Browser $browser
     * @param string $query
     * @return Browser
     */
    public function searchCampaign(Browser $browser, string $query): Browser
    {
        return $browser->type('@search-input', $query)
            ->pause(500); // Allow time for search to process
    }
}