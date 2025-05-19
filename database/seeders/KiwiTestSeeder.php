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

        Contact::create([
            'company_id' => $company->id,
            'first_name' => 'Angela',
            'last_name' => 'Chen',
            'email' => 'doug@samplepal.net',
            'cell_phone' => '(415) 555-2345',
            'office_phone' => '(415) 555-8978',
            'job_title' => 'Head of Operations',
            'has_been_contacted' => false,
            'deal_status' => 'none',
            'notes' => 'Angela oversees all operations and facilities. She\'s the main decision maker for office improvements.',
            'relevance_score' => 90,
        ]);

        Contact::create([
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Rodriguez',
            'email' => 'd.steinberg79@gmail.com',
            'cell_phone' => '(415) 555-3456',
            'office_phone' => '(415) 555-8979',
            'job_title' => 'Lead Engineer',
            'has_been_contacted' => false,
            'deal_status' => 'none',
            'notes' => 'Michael leads the engineering team and has expressed interest in smart lighting systems.',
            'relevance_score' => 75,
        ]);

    }
}
