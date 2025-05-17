<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScreenshotHelper extends DuskTestCase
{
    /**
     * Take a screenshot of each page to check the UI content.
     */
    public function test_take_screenshots_of_pages(): void
    {
        $this->browse(function (Browser $browser) {
            // Visit login page
            $browser->visit('/login')
                ->screenshot('login-page');
            
            // Register a user and login
            $user = \App\Models\User::factory()->create();
            
            // Login and visit dashboard
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->screenshot('dashboard-page');
                
            // Visit settings pages
            $browser->visit('/settings/profile')
                ->screenshot('profile-page');
                
            $browser->visit('/settings/password')
                ->screenshot('password-page');
                
            $browser->visit('/settings/appearance')
                ->screenshot('appearance-page');
                
            // Visit main pages
            $browser->visit('/companies')
                ->screenshot('companies-page');
                
            $browser->visit('/contacts')
                ->screenshot('contacts-page');
                
            $browser->visit('/campaigns')
                ->screenshot('campaigns-page');
                
            // Visit forgot password page
            $browser->visit('/forgot-password')
                ->screenshot('forgot-password-page');
        });
    }
}