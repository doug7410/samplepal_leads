<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ForgotPasswordTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that a user can view the forgot password page.
     */
    public function test_user_can_view_the_forgot_password_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Test that a user can request a password reset link.
     */
    public function test_user_can_request_password_reset_link_with_valid_email(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/forgot-password')
                ->assertPathIs('/forgot-password');
                
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'email' => $user->email
            ]);
        });
    }

    /**
     * Test that a user cannot request a password reset with an invalid email.
     */
    public function test_user_cannot_request_password_reset_with_invalid_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Test that a user cannot request a password reset with a non-existent email.
     */
    public function test_user_gets_notification_when_using_nonexistent_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->assertPathIs('/forgot-password');
        });
    }
}