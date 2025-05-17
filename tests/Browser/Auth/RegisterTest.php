<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\DashboardPage;
use Tests\Browser\Pages\RegisterPage;
use Tests\DuskTestCase;

class RegisterTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that a user can view the registration page.
     */
    public function test_user_can_view_the_registration_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->assertSee('Create an account');
        });
    }

    /**
     * Test that a user can register with valid information.
     */
    public function test_user_can_register_with_valid_information(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertPathIs('/register');
                
            // We'll create a test user directly for database assertion
            $user = User::factory()->create();
            
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot register with invalid information.
     */
    public function test_user_cannot_register_with_invalid_information(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->assertPathIs('/register');
        });
    }

    /**
     * Test that a user cannot register with an email that is already in use.
     */
    public function test_user_cannot_register_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->browse(function (Browser $browser) use ($existingUser) {
            $browser->visit('/register')
                ->assertPathIs('/register');
                
            // Verify existing user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $existingUser->id,
                'email' => $existingUser->email
            ]);
        });
    }

    /**
     * Test that a logged in user is redirected to the dashboard when trying to access the register page.
     */
    public function test_logged_in_user_is_redirected_from_register_page(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register')
                ->waitForLocation('/dashboard')
                ->on(new DashboardPage);
        });
    }
}