<?php

namespace Tests\Browser\Pages\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class ProfilePage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/settings/profile';
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
            '@name' => 'input[name="name"]',
            '@email' => 'input[name="email"]',
            '@profile-save-button' => '[data-testid="profile-save"]',
            '@delete-account-button' => '[data-testid="delete-account"]',
            '@confirm-password' => '#password',
            '@confirm-button' => 'button[type="submit"]',
        ];
    }

    /**
     * Update the user profile information.
     *
     * @param Browser $browser
     * @param string $name
     * @param string $email
     * @return Browser
     */
    public function updateProfile(Browser $browser, string $name, string $email): Browser
    {
        return $browser->type('@name', $name)
            ->type('@email', $email)
            ->click('@profile-save-button')
            ->waitForText('Profile updated successfully');
    }

    /**
     * Delete the user account.
     *
     * @param Browser $browser
     * @param string $password
     * @return Browser
     */
    public function deleteAccount(Browser $browser, string $password): Browser
    {
        $browser->click('@delete-account-button')
            ->waitFor('#password')
            ->type('@confirm-password', $password)
            ->click('@confirm-button');
        
        return $browser->waitForLocation('/login');
    }
}