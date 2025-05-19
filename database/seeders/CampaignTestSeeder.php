<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user in the system to assign as campaign owner
        $user = User::firstOrFail();

        // Find the Kiwi Tech Lab company
        $company = Company::where('company_name', 'Kiwi Tech Lab')->firstOrFail();

        // Create a company campaign
        $campaign = Campaign::create([
            'name' => 'Q2 Product Launch - Kiwi Tech Lab',
            'description' => 'Campaign to introduce new lighting products to Kiwi Tech Lab',
            'subject' => 'Hello {{recipients}} - New Lighting Solutions for Kiwi Tech Lab',
            'content' => '<p>Hello {{recipients}},</p>

<p>I wanted to reach out to you all at Kiwi Tech Lab about our latest smart lighting solutions that would be perfect for your new office space.</p>

<p>Our new product line includes:</p>
<ul>
  <li>Energy-efficient LED panels with customizable color temperatures</li>
  <li>Motion-activated lighting systems with occupancy analytics</li>
  <li>Smart lighting controls compatible with your existing building management system</li>
</ul>

<p>I\'d love to schedule a demo for your team. Would next Tuesday work for a quick call?</p>

<p>Best regards,<br>
Angela<br>
Sample Pal Lighting Solutions</p>',
            'from_email' => 'angela@samplepal.net',
            'from_name' => 'Angela from Sample Pal',
            'reply_to' => 'angela@samplepal.net',
            'status' => Campaign::STATUS_DRAFT,
            'type' => Campaign::TYPE_COMPANY,
            'user_id' => $user->id,
        ]);

        // Attach the Kiwi Tech Lab company to the campaign
        $campaign->companies()->attach($company->id);

        // Output confirmation
        $this->command->info('Created company campaign with Kiwi Tech Lab');
    }
}
