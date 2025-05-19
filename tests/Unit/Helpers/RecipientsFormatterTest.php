<?php

namespace Tests\Unit\Helpers;

use App\Helpers\RecipientsFormatter;
use App\Models\Contact;
use PHPUnit\Framework\TestCase;

class RecipientsFormatterTest extends TestCase
{
    /** @test */
    public function it_formats_a_single_recipient()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug', $result);
    }

    /** @test */
    public function it_formats_two_recipients()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
            $this->createContact('Angela', 'Todd'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug and Angela', $result);
    }

    /** @test */
    public function it_formats_three_or_more_recipients()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
            $this->createContact('Angela', 'Todd'),
            $this->createContact('John', 'Smith'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug, Angela and John', $result);
    }

    /** @test */
    public function it_formats_four_recipients()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
            $this->createContact('Angela', 'Todd'),
            $this->createContact('John', 'Smith'),
            $this->createContact('Sara', 'Johnson'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug, Angela, John and Sara', $result);
    }

    /** @test */
    public function it_includes_last_names_for_duplicate_first_names()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
            $this->createContact('Doug', 'Todd'),
            $this->createContact('Angela', 'Smith'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug Steinberg, Doug Todd and Angela', $result);
    }

    /** @test */
    public function it_includes_last_names_only_for_duplicates_not_all_with_same_first_name()
    {
        $contacts = [
            $this->createContact('Doug', 'Steinberg'),
            $this->createContact('Doug', 'Todd'),
            $this->createContact('Doug', 'Johnson'),
            $this->createContact('Angela', 'Smith'),
        ];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('Doug Steinberg, Doug Todd, Doug Johnson and Angela', $result);
    }

    /** @test */
    public function it_handles_empty_contacts_list()
    {
        $contacts = [];

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_handles_null_values()
    {
        $contacts = null;

        $result = RecipientsFormatter::format($contacts);

        $this->assertEquals('', $result);
    }

    /**
     * Create a mock Contact object for testing
     */
    private function createContact($firstName, $lastName): Contact
    {
        $contact = $this->createStub(Contact::class);
        $contact->method('__get')->willReturnMap([
            ['first_name', $firstName],
            ['last_name', $lastName],
        ]);

        return $contact;
    }
}
