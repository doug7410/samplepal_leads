<?php

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Config;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unsubscribe route works correctly', function () {
    // Create a user
    $user = User::factory()->create();
    
    // Create a campaign and contact
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Campaign',
        'subject' => 'Test Subject',
    ]);
    
    $contact = Contact::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);
    
    // Generate a valid token
    $key = Config::get('app.key');
    $campaignId = $campaign->id;
    $contactId = $contact->id;
    $email = $contact->email;
    $data = $campaignId.'|'.$contactId.'|'.$email;
    $token = hash_hmac('sha256', $data, $key);
    
    // Test the unsubscribe route
    $response = $this->get(route('email.unsubscribe', [
        'campaign' => $campaignId,
        'contact' => $contactId,
        'token' => $token,
    ]));
    
    // Verify the route works
    $response->assertOk();
    
    // Verify the contact was marked as unsubscribed
    $this->assertTrue($contact->fresh()->has_unsubscribed);
    $this->assertNotNull($contact->fresh()->unsubscribed_at);
});