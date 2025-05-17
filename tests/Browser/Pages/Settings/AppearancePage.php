<?php

namespace Tests\Browser\Pages\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class AppearancePage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/settings/appearance';
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
            '@appearance-heading' => 'h1',
            '@theme-toggle' => '[data-testid="theme-toggle"]',
            '@light-theme' => '[data-testid="light-theme"]',
            '@dark-theme' => '[data-testid="dark-theme"]',
            '@system-theme' => '[data-testid="system-theme"]',
        ];
    }

    /**
     * Select a theme.
     *
     * @param Browser $browser
     * @param string $theme (light, dark, system)
     * @return Browser
     */
    public function selectTheme(Browser $browser, string $theme): Browser
    {
        $selector = match ($theme) {
            'light' => '@light-theme',
            'dark' => '@dark-theme',
            'system' => '@system-theme',
            default => throw new \InvalidArgumentException("Theme must be one of: light, dark, system")
        };
        
        return $browser->click($selector);
    }
}