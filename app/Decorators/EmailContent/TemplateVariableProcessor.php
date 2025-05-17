<?php

namespace App\Decorators\EmailContent;

class TemplateVariableProcessor extends EmailContentDecorator
{
    /**
     * Replace template variables with actual contact and campaign data
     *
     * @param  string  $content  The content processed by previous decorators
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The content with template variables replaced
     */
    protected function applyDecoration(string $content, object $campaign, object $contact): string
    {
        $replacements = [
            '{{first_name}}' => $contact->first_name ?? '',
            '{{last_name}}' => $contact->last_name ?? '',
            '{{full_name}}' => trim(($contact->first_name ?? '').' '.($contact->last_name ?? '')),
            '{{email}}' => $contact->email ?? '',
            '{{company}}' => $contact->company->name ?? '',
            '{{job_title}}' => $contact->job_title ?? '',
            '{{campaign_name}}' => $campaign->name ?? '',
            '{{campaign_id}}' => (string) ($campaign->id ?? ''),
            '{{date}}' => now()->format('F j, Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
