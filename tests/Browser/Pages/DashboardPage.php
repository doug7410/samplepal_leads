<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class DashboardPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/dashboard';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@dashboard-heading' => 'h1',
            '@companies-link' => '[href="/companies"]',
            '@contacts-link' => '[href="/contacts"]',
            '@campaigns-link' => '[href="/campaigns"]',
            '@settings-link' => '[href="/settings/profile"]',
        ];
    }
}
