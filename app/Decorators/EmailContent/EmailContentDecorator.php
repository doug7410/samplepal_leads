<?php

namespace App\Decorators\EmailContent;

abstract class EmailContentDecorator implements EmailContentProcessorInterface
{
    /**
     * The wrapped email content processor
     */
    protected EmailContentProcessorInterface $wrappedProcessor;

    /**
     * Constructor
     *
     * @param  EmailContentProcessorInterface  $processor  The processor to wrap
     */
    public function __construct(EmailContentProcessorInterface $processor)
    {
        $this->wrappedProcessor = $processor;
    }

    /**
     * Process the email content by first delegating to the wrapped processor
     * then applying this decorator's specific processing
     *
     * @param  string  $content  The raw email content
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The processed content
     */
    public function process(string $content, object $campaign, object $contact): string
    {
        // First, let the wrapped processor do its work
        $processedContent = $this->wrappedProcessor->process($content, $campaign, $contact);

        // Then, apply this decorator's specific processing
        return $this->applyDecoration($processedContent, $campaign, $contact);
    }

    /**
     * Apply this decorator's specific processing to the content
     *
     * @param  string  $content  The content processed by previous decorators
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The further processed content
     */
    abstract protected function applyDecoration(string $content, object $campaign, object $contact): string;
}
