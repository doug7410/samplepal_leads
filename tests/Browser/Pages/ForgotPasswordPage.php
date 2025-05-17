<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class ForgotPasswordPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/forgot-password';
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
            '@email' => 'input[name="email"]',
            '@submit-button' => 'button[type="submit"]',
            '@login-link' => '[href="/login"]',
        ];
    }

    /**
     * Request a password reset link.
     *
     * @param Browser $browser
     * @param string $email
     * @return Browser
     */
    public function requestPasswordReset(Browser $browser, string $email): Browser
    {
        return $browser->type('@email', $email)
            ->click('@submit-button');
    }
}