<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BasicNavigationTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test basic navigation is working.
     */
    public function test_basic_navigation(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/')
                ->assertPathIs('/')
                ->visit('/login')
                ->assertPathIs('/login')
                ->loginAs($user)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard');
        });
    }
}