<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class CreateCampaignPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/campaigns/create';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Create Campaign');
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@name' => 'input[name="name"]',
            '@subject' => 'input[name="subject"]',
            '@sender_name' => 'input[name="sender_name"]',
            '@sender_email' => 'input[name="sender_email"]',
            '@content' => 'textarea[name="content"]',
            '@submit-button' => 'button[type="submit"]',
            '@cancel-button' => 'a[href="/campaigns"]',
        ];
    }

    /**
     * Fill and submit the campaign form.
     */
    public function createCampaign(Browser $browser, array $data): Browser
    {
        $browser->type('@name', $data['name'])
            ->type('@subject', $data['subject'])
            ->type('@sender_name', $data['sender_name'])
            ->type('@sender_email', $data['sender_email'])
            ->type('@content', $data['content'])
            ->click('@submit-button');

        return $browser->waitForLocation('/campaigns');
    }
}
