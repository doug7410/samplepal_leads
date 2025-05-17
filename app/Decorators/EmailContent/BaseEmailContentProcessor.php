<?php

namespace App\Decorators\EmailContent;

class BaseEmailContentProcessor implements EmailContentProcessorInterface
{
    /**
     * Process the raw email content
     *
     * @param  string  $content  The raw email content
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The processed content
     */
    public function process(string $content, object $campaign, object $contact): string
    {
        return $content;
    }
}
