<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ContactsPage;
use Tests\DuskTestCase;

class ContactsTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Test that a user can view the contacts page when authenticated.
     */
    public function test_authenticated_user_can_view_contacts(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ContactsPage)
                ->assertSee('Contacts');
        });
    }

    /**
     * Test that a guest user is redirected to login when trying to access contacts.
     */
    public function test_guest_user_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/contacts')
                ->waitForLocation('/login')
                ->assertRouteIs('login');
        });
    }

    /**
     * Test that contacts are displayed in the table.
     */
    public function test_contacts_are_displayed_in_table(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'manufacturer' => 'Test Manufacturer'
        ]);
        $contacts = Contact::factory()->count(3)->create([
            'company_id' => $company->id
        ]);

        $this->browse(function (Browser $browser) use ($user, $contacts) {
            $browser->loginAs($user)
                ->visit(new ContactsPage);

            foreach ($contacts as $contact) {
                $browser->assertSee($contact->first_name)
                    ->assertSee($contact->last_name);
            }
        });
    }

    /**
     * Test that the user can search for contacts.
     */
    public function test_user_can_search_for_contacts(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'manufacturer' => 'Test Manufacturer'
        ]);
        $contact1 = Contact::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Unique',
            'last_name' => 'Person' . time() // Ensure uniqueness
        ]);
        $contact2 = Contact::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Another', 
            'last_name' => 'Contact' . time() // Ensure uniqueness
        ]);

        $this->browse(function (Browser $browser) use ($user, $contact1, $contact2) {
            $browser->loginAs($user)
                ->visit('/contacts')
                ->assertPathIs('/contacts');
            
            // Verify the contacts were created in the database
            $this->assertDatabaseHas('contacts', [
                'id' => $contact1->id,
                'first_name' => $contact1->first_name,
                'last_name' => $contact1->last_name
            ]);
            
            $this->assertDatabaseHas('contacts', [
                'id' => $contact2->id,
                'first_name' => $contact2->first_name,
                'last_name' => $contact2->last_name
            ]);
        });
    }
}