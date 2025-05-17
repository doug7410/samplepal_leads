<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class RegisterPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/register';
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
            '@password' => 'input[name="password"]',
            '@password-confirmation' => 'input[name="password_confirmation"]',
            '@register-button' => 'button[type="submit"]',
            '@login-link' => '[href="/login"]',
        ];
    }

    /**
     * Register a new user with the given information.
     *
     * @param Browser $browser
     * @param string $name
     * @param string $email
     * @param string $password
     * @return Browser
     */
    public function register(Browser $browser, string $name, string $email, string $password): Browser
    {
        return $browser->type('@name', $name)
            ->type('@email', $email)
            ->type('@password', $password)
            ->type('@password-confirmation', $password)
            ->click('@register-button')
            ->waitForLocation('/dashboard');
    }
}