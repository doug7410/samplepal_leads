<?php

/**
 * Test script to verify SendCampaignEmailJob with unsubscribe route changes
 *
 * Run with: php test_campaign_email.php
 */

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// Set up a console logger so we can see logs
$monolog = Log::getLogger();
$monolog->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));

echo "Starting SendCampaignEmailJob Test...\n\n";

// Make sure the unsubscribe route exists
$routeExists = false;
foreach (Route::getRoutes() as $route) {
    if ($route->getName() === 'email.unsubscribe') {
        $routeExists = true;
        echo "✅ Unsubscribe route exists: {$route->uri}\n";
        break;
    }
}

if (! $routeExists) {
    echo "❌ ERROR: Unsubscribe route 'email.unsubscribe' does not exist!\n";
    exit(1);
}

try {
    // Configure the mail driver to use the array mailer for testing
    Config::set('mail.default', 'array');
    Config::set('mail.mailers.array', [
        'transport' => 'array',
    ]);

    echo "Using array mailer for testing...\n";

    // Use a transaction to ensure we don't modify the database permanently
    DB::beginTransaction();

    // Create a test user if one doesn't exist
    $user = User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]
    );

    // Create a test company or get first available
    $company = Company::first();
    if (! $company) {
        $company = Company::create([
            'user_id' => $user->id,
            'website' => 'https://example.com',
            'description' => 'Test company for email testing',
        ]);
    }

    // Create a test contact or get first available
    $contact = Contact::first();
    if (! $contact) {
        $contact = Contact::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'email' => 'contact@example.com',
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'job_title' => 'Tester',
            'relevance_score' => 80,
            'deal_status' => 'none',
        ]);
    }

    // Create a test campaign or get first available
    $campaign = Campaign::first();
    if (! $campaign) {
        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Test Unsubscribe Campaign',
            'subject' => 'Test Subject with {{first_name}}',
            'content' => '<p>Hello {{first_name}},</p><p>This is a test email to verify unsubscribe functionality.</p>',
            'status' => Campaign::STATUS_IN_PROGRESS,
        ]);
    }

    echo "Test data created:\n";
    echo "- User: {$user->name} ({$user->email})\n";
    echo "- Company: {$company->name}\n";
    echo "- Contact: {$contact->first_name} {$contact->last_name} ({$contact->email})\n";
    echo "- Campaign: {$campaign->name}\n";

    // Check if campaign contact already exists
    $campaignContact = CampaignContact::where('campaign_id', $campaign->id)
        ->where('contact_id', $contact->id)
        ->first();

    if ($campaignContact) {
        // Reset the status for testing
        $campaignContact->status = CampaignContact::STATUS_PENDING;
        $campaignContact->message_id = null;
        $campaignContact->sent_at = null;
        $campaignContact->save();
        echo "- Reset existing campaign contact to pending status\n";
    } else {
        // Create a campaign contact
        $campaignContact = new CampaignContact([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => CampaignContact::STATUS_PENDING,
        ]);
        $campaignContact->save();
        echo "- Created new campaign contact\n";
    }

    echo "\nExecuting SendCampaignEmailJob...\n";

    // Clear previous mail data
    Mail::fake();

    // Execute the job directly
    $job = new SendCampaignEmailJob($campaignContact);
    $job->handle(app(\App\Services\MailServiceInterface::class));

    // Refresh the campaign contact from DB
    $campaignContact->refresh();

    // Check the result
    if ($campaignContact->status === CampaignContact::STATUS_SENT) {
        echo "✅ Job executed successfully! Status: {$campaignContact->status}\n";

        // Get the sent emails
        $emails = Mail::getSymfonyTransport()->messages();

        if (count($emails) > 0) {
            echo "✅ Email was sent successfully!\n";

            // Check for unsubscribe link in the email content
            $email = $emails[0];
            $content = $email->getHtmlBody();

            if (strpos($content, 'unsubscribe') !== false) {
                echo "✅ Email contains unsubscribe text\n";

                if (strpos($content, 'email/unsubscribe') !== false) {
                    echo "✅ Email contains correct unsubscribe URL path\n";
                } else {
                    echo "❌ WARNING: Email doesn't contain the expected unsubscribe URL path\n";
                }
            } else {
                echo "❌ WARNING: Email doesn't contain unsubscribe text\n";
            }
        } else {
            echo "❌ ERROR: No emails were sent!\n";
        }
    } else {
        echo "❌ ERROR: Job failed! Status: {$campaignContact->status}\n";

        if ($campaignContact->failure_reason) {
            echo "Error reason: {$campaignContact->failure_reason}\n";

            // Check specifically for the unsubscribe route error
            if (strpos($campaignContact->failure_reason, 'Route [email.unsubscribe] not defined') !== false) {
                echo "❌ CRITICAL: The unsubscribe route error is still occurring!\n";
            }
        }
    }

    // Roll back all database changes
    DB::rollBack();
    echo "\nTest complete. All database changes have been rolled back.\n";

} catch (\Exception $e) {
    // Make sure we roll back on any exception
    DB::rollBack();
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString()."\n";
}
