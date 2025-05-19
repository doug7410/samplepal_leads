<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

abstract class BaseBrowserTestCase extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Create a user that can be used for testing.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Login the given user or create a new one.
     */
    protected function login(Browser $browser, ?User $user = null): Browser
    {
        $user = $user ?? $this->createUser();

        return $browser->visit('/login')
            ->type('email', $user->email)
            ->type('password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard');
    }

    /**
     * Log out the current user.
     */
    protected function logout(Browser $browser): Browser
    {
        return $browser->click('@user-menu')
            ->waitFor('@logout-button')
            ->click('@logout-button')
            ->waitForLocation('/login');
    }

    /**
     * Navigate to a route using the given route name and parameters.
     */
    protected function navigateToRoute(Browser $browser, string $routeName, array $parameters = []): Browser
    {
        $url = route($routeName, $parameters);

        return $browser->visit($url);
    }
}
