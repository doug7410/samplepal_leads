<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page as BasePage;

abstract class Page extends BasePage
{
    /**
     * Get the global element shortcuts for the site.
     *
     * @return array<string, string>
     */
    public static function siteElements(): array
    {
        return [
            // Navigation/Layout
            '@app-sidebar' => '.app-sidebar',
            '@app-content' => '.app-content',
            '@app-header' => '.app-header',

            // User menu
            '@user-menu' => '.nav-user button',
            '@logout-button' => '[data-testid="logout-button"]',

            // Common form elements
            '@submit-button' => 'button[type="submit"]',
            '@cancel-button' => 'button[data-cancel]',
        ];
    }
}
