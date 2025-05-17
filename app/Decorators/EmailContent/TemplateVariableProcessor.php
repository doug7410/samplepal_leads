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
        
        // Handle {{recipients}} variable for company campaigns
        if (strpos($content, '{{recipients}}') !== false && isset($contact->company_id) && $contact->company_id) {
            // Get all contacts for the company
            $companyContacts = $contact->company->contacts()
                ->whereNotNull('email')
                ->where('id', '!=', $contact->id) // Exclude current contact
                ->get();
            
            // Add current contact to the beginning
            $companyContacts->prepend($contact);
            
            // Format the recipients list using RecipientsFormatter
            $replacements['{{recipients}}'] = \App\Helpers\RecipientsFormatter::format($companyContacts);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
