<?php

namespace Tests\Unit\Http\Controllers;

use App\Enums\DealStatus;
use App\Http\Controllers\EmailTrackingController;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Services\MailServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class EmailTrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Campaign $campaign;

    protected Contact $contact;

    protected CampaignContact $campaignContact;

    protected MailServiceInterface $mailService;

    protected EmailTrackingController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Use Log facade spy instead of mocking
        Log::spy();

        // Create test data
        $this->campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => 'in_progress',
        ]);

        $this->contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'has_been_contacted' => false,
            'deal_status' => DealStatus::None,
        ]);

        $this->campaignContact = CampaignContact::create([
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contact->id,
            'status' => 'sent', // Initial status
            'sent_at' => now()->subHour(),
        ]);

        // Mock the mail service
        $this->mailService = Mockery::mock(MailServiceInterface::class);
        $this->mailService->shouldReceive('verifyTrackingToken')
            ->with('valid-token', $this->campaign->id, $this->contact->id)
            ->andReturn(true);

        $this->mailService->shouldReceive('verifyTrackingToken')
            ->with('invalid-token', Mockery::any(), Mockery::any())
            ->andReturn(false);

        $this->controller = new EmailTrackingController($this->mailService);
    }

    public function test_handle_webhook_processes_valid_resend_event()
    {
        // Mock the webhook secret environment variable
        Config::set('services.resend.webhook_secret', 'test-secret');

        // Create a mock request with Resend webhook data
        $payload = [
            'type' => 'email.delivered',
            'data' => [
                'email_id' => 'msg_12345',
                'headers' => [
                    ['name' => 'X-Campaign-ID', 'value' => (string) $this->campaign->id],
                    ['name' => 'X-Contact-ID', 'value' => (string) $this->contact->id],
                ],
            ],
        ];

        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Mock the request content
        $request->setMethod('POST');
        $request->headers->set('Svix-Signature', 'v1,signature');
        $request->headers->set('Svix-Id', 'msg_123');
        $request->headers->set('Svix-Timestamp', time());

        // Call the handleWebhook method
        $response = $this->controller->handleWebhook($request);

        // Check response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());

        // Check that the event was recorded
        $this->assertDatabaseHas('email_events', [
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contact->id,
            'event_type' => 'delivered',
        ]);

        // Check that the campaign contact was updated
        $this->campaignContact->refresh();
        $this->assertEquals('delivered', $this->campaignContact->status);
        $this->assertNotNull($this->campaignContact->delivered_at);
    }

    public function test_handle_webhook_verifies_svix_signature_with_whsec_prefix()
    {
        Config::set('app.env', 'production');

        $secretRaw = random_bytes(24);
        $whsecSecret = 'whsec_'.base64_encode($secretRaw);
        Config::set('services.resend.webhook_secret', $whsecSecret);

        $payload = [
            'type' => 'email.delivered',
            'data' => [
                'email_id' => 'msg_12345',
                'headers' => [
                    ['name' => 'X-Campaign-ID', 'value' => (string) $this->campaign->id],
                    ['name' => 'X-Contact-ID', 'value' => (string) $this->contact->id],
                ],
            ],
        ];

        $body = json_encode($payload);
        $messageId = 'msg_test_'.uniqid();
        $timestamp = (string) time();

        $toSign = $messageId.'.'.$timestamp.'.'.$body;
        $signature = base64_encode(hash_hmac('sha256', $toSign, $secretRaw, true));

        $request = Request::create('/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $request->headers->set('Svix-Id', $messageId);
        $request->headers->set('Svix-Timestamp', $timestamp);
        $request->headers->set('Svix-Signature', 'v1,'.$signature);

        $response = $this->controller->handleWebhook($request);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('email_events', [
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contact->id,
            'event_type' => 'delivered',
        ]);
    }

    public function test_handle_webhook_rejects_invalid_signature_in_production()
    {
        Config::set('app.env', 'production');
        Config::set('services.resend.webhook_secret', 'whsec_'.base64_encode(random_bytes(24)));

        $payload = ['type' => 'email.delivered', 'data' => ['email_id' => 'msg_12345']];
        $body = json_encode($payload);

        $request = Request::create('/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $request->headers->set('Svix-Id', 'msg_test');
        $request->headers->set('Svix-Timestamp', (string) time());
        $request->headers->set('Svix-Signature', 'v1,invalidsignature');

        $response = $this->controller->handleWebhook($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_handle_webhook_handles_invalid_payload()
    {
        // Create a request with an invalid payload (missing 'type')
        $payload = [
            'data' => [
                'email_id' => 'msg_12345',
            ],
        ];

        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Call the handleWebhook method
        $response = $this->controller->handleWebhook($request);

        // Check response
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid payload', $response->getContent());

        // Check that no event was recorded
        $this->assertDatabaseCount('email_events', 0);
    }
}
