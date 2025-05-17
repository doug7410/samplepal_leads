<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class ContactsPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/contacts';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Contacts');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@contacts-heading' => 'h1',
            '@contacts-table' => '.contacts-table',
            '@create-contact-button' => '[data-testid="create-contact-button"]',
            '@filter-button' => '[data-testid="filter-button"]',
            '@search-input' => 'input[type="search"]',
        ];
    }

    /**
     * Search for a contact.
     *
     * @param Browser $browser
     * @param string $query
     * @return Browser
     */
    public function searchContact(Browser $browser, string $query): Browser
    {
        return $browser->type('@search-input', $query)
            ->pause(500); // Allow time for search to process
    }
}