<?php

namespace App\Decorators\EmailContent;

class EmailContentProcessorFactory
{
    /**
     * Create a minimal processor with only basic functionality
     */
    public static function createBasicProcessor(): EmailContentProcessorInterface
    {
        // Start with the base processor
        $processor = new BaseEmailContentProcessor;

        // Add the HTML sanitizer
        $processor = new HtmlSanitizerProcessor($processor);

        // Add the template variable processor
        return new TemplateVariableProcessor($processor);
    }
}
