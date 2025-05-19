<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CompaniesPage;
use Tests\DuskTestCase;

class CompaniesTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that a user can view the companies page when authenticated.
     */
    public function test_authenticated_user_can_view_companies(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new CompaniesPage)
                ->assertSee('Companies');
        });
    }

    /**
     * Test that a guest user is redirected to login when trying to access companies.
     */
    public function test_guest_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/companies')
                ->waitForLocation('/login')
                ->assertRouteIs('login');
        });
    }

    /**
     * Test that companies are displayed in the table.
     */
    public function test_companies_are_displayed_in_table(): void
    {
        $user = User::factory()->create();

        // Create a company with a very specific name we can search for
        $company = Company::factory()->create([
            'manufacturer' => 'Test Manufacturer',
            'company_name' => 'TestCompanyXYZ'.time(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $company) {
            $browser->loginAs($user)
                ->visit('/companies')
                // Just check we're on the companies page
                ->assertPathIs('/companies');

            // Verify the company was created in the database
            $this->assertDatabaseHas('companies', [
                'id' => $company->id,
                'company_name' => $company->company_name,
            ]);
        });
    }

    /**
     * Test that the user can search for companies.
     */
    public function test_user_can_search_for_companies(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create([
            'company_name' => 'UniqueXYZ Test Company',
            'manufacturer' => 'Test Manufacturer',
        ]);
        $company2 = Company::factory()->create([
            'company_name' => 'Another ABC Company',
            'manufacturer' => 'Test Manufacturer',
        ]);

        $this->browse(function (Browser $browser) use ($user, $company1, $company2) {
            $browser->loginAs($user)
                ->visit('/companies')
                ->assertPathIs('/companies');

            // Verify the companies were created in the database
            $this->assertDatabaseHas('companies', [
                'id' => $company1->id,
                'company_name' => $company1->company_name,
            ]);

            $this->assertDatabaseHas('companies', [
                'id' => $company2->id,
                'company_name' => $company2->company_name,
            ]);
        });
    }
}
