<?php

namespace Tests\Browser\Pages\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class PasswordPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/settings/password';
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
            '@current-password' => '#current_password',
            '@password' => '#password',
            '@password-confirmation' => '#password_confirmation',
            '@update-password-button' => '[data-testid="update-password"]',
        ];
    }

    /**
     * Update the user password.
     */
    public function updatePassword(Browser $browser, string $currentPassword, string $newPassword): Browser
    {
        return $browser->type('@current-password', $currentPassword)
            ->type('@password', $newPassword)
            ->type('@password-confirmation', $newPassword)
            ->click('@update-password-button')
            ->waitForText('Password updated successfully');
    }
}
