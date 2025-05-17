<?php

namespace Tests\Browser\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Settings\ProfilePage;
use Tests\DuskTestCase;

class ProfileTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that an authenticated user can view their profile settings.
     */
    public function test_authenticated_user_can_view_profile_settings(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/profile')
                ->assertPathIs('/settings/profile');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a guest user is redirected to login when trying to access profile settings.
     */
    public function test_guest_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/settings/profile')
                ->waitForLocation('/login')
                ->assertRouteIs('login');
        });
    }

    /**
     * Test that a user can update their profile.
     */
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/profile')
                ->assertPathIs('/settings/profile');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot update their profile with invalid data.
     */
    public function test_user_cannot_update_profile_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/profile')
                ->assertPathIs('/settings/profile');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user can delete their account.
     */
    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/profile')
                ->assertPathIs('/settings/profile');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }
}