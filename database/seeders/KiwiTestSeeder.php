<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class KiwiTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Kiwi Tech Lab company
        $company = Company::create([
            'manufacturer' => 'Kiwi',
            'company_name' => 'Kiwi Tech Lab',
            'company_phone' => '(415) 555-8976',
            'address_line_1' => '123 Innovation Way',
            'address_line_2' => 'Suite 400',
            'city_or_region' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94105',
            'country' => 'United States',
            'email' => 'info@kiwitechlab.net',
            'website' => 'https://kiwitechlab.net',
            'contact_name' => 'Doug Steinberg',
            'contact_phone' => '(415) 555-8977',
            'contact_email' => 'doug@kiwitechlab.net',
        ]);

        // Create Doug as a contact
        Contact::create([
            'company_id' => $company->id,
            'first_name' => 'Doug',
            'last_name' => 'Steinberg',
            'email' => 'doug@kiwitechlab.net',
            'cell_phone' => '(415) 555-1234',
            'office_phone' => '(415) 555-8977',
            'job_title' => 'Chief Technology Officer',
            'has_been_contacted' => false,
            'deal_status' => 'none',
            'notes' => 'Doug is the CTO and founder of Kiwi Tech Lab. He\'s interested in exploring lighting solutions for their new office building.',
            'relevance_score' => 80,
        ]);
    }
}