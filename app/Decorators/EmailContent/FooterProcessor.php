<?php

namespace App\Decorators\EmailContent;

use Illuminate\Support\Facades\Config;

class FooterProcessor extends EmailContentDecorator
{
    /**
     * Add a footer to the email content
     *
     * @param  string  $content  The content processed by previous decorators
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The content with footer added
     */
    protected function applyDecoration(string $content, object $campaign, object $contact): string
    {
        // Check if the content already has a footer
        if (str_contains($content, '<!-- Footer -->')) {
            return $content;
        }

        $companyName = Config::get('app.name');
        $campaignId = $campaign->id ?? 0;
        $contactId = $contact->id ?? 0;

        try {
            $unsubscribeUrl = route('email.unsubscribe', [
                'campaign' => $campaignId,
                'contact' => $contactId,
                'token' => $this->generateUnsubscribeToken($campaign, $contact),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating unsubscribe URL: '.$e->getMessage());
            $unsubscribeUrl = '#'; // Fallback to a harmless URL if route generation fails
        }

        $footer = "
        <hr style='margin-top: 30px; border-top: 1px solid #e0e0e0;'>
        <!-- Footer -->
        <div style='margin-top: 20px; color: #666; font-size: 12px; text-align: center;'>
            <p>This email was sent by {$companyName}.</p>
            <p>If you no longer wish to receive these emails, you may <a href='{$unsubscribeUrl}'>unsubscribe here</a>.</p>
        </div>
        ";

        // Add the footer at the end of the body or before the closing body tag
        if (preg_match('/<\/body>/', $content)) {
            return preg_replace('/<\/body>/', $footer.'</body>', $content);
        } else {
            return $content.$footer;
        }
    }

    /**
     * Generate a simple unsubscribe token
     */
    protected function generateUnsubscribeToken(object $campaign, object $contact): string
    {
        $key = Config::get('app.key');
        $campaignId = $campaign->id ?? 0;
        $contactId = $contact->id ?? 0;
        $email = $contact->email ?? '';

        $data = $campaignId.'|'.$contactId.'|'.$email;

        return hash_hmac('sha256', $data, $key);
    }
}
