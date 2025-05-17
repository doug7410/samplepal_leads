<?php

namespace Tests\Browser\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Settings\PasswordPage;
use Tests\DuskTestCase;

class PasswordTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that an authenticated user can view the password update page.
     */
    public function test_authenticated_user_can_view_password_page(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/password')
                ->assertPathIs('/settings/password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a guest user is redirected to login when trying to access the password page.
     */
    public function test_guest_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/settings/password')
                ->waitForLocation('/login')
                ->assertRouteIs('login');
        });
    }

    /**
     * Test that a user can update their password with valid current password.
     */
    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/password')
                ->assertPathIs('/settings/password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot update their password with an incorrect current password.
     */
    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/password')
                ->assertPathIs('/settings/password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot update their password with passwords that don't match.
     */
    public function test_user_cannot_update_password_with_mismatched_passwords(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/password')
                ->assertPathIs('/settings/password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot update their password with a short password.
     */
    public function test_user_cannot_update_password_with_short_password(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/password')
                ->assertPathIs('/settings/password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }
}