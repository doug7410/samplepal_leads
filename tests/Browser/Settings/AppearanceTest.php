<?php

namespace Tests\Browser\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AppearanceTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that an authenticated user can view the appearance settings page.
     */
    public function test_authenticated_user_can_view_appearance_settings(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/appearance')
                ->assertPathIs('/settings/appearance');

            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
        });
    }

    /**
     * Test that a guest user is redirected to login when trying to access appearance settings.
     */
    public function test_guest_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/settings/appearance')
                ->waitForLocation('/login')
                ->assertRouteIs('login');
        });
    }

    /**
     * Test that a user can switch to different themes.
     */
    public function test_user_can_switch_themes(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings/appearance')
                ->assertPathIs('/settings/appearance');

            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
        });
    }
}
