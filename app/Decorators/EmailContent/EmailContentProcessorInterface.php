<?php

namespace App\Decorators\EmailContent;

/**
 * Interface for email content processors
 */
interface EmailContentProcessorInterface
{
    /**
     * Process the email content with any transformations
     *
     * @param  string  $content  The raw email content
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The processed content
     */
    public function process(string $content, object $campaign, object $contact): string;
}
