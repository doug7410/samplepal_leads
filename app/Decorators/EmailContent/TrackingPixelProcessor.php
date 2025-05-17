<?php

namespace App\Decorators\EmailContent;

use App\Strategies\EmailTracking\TrackingStrategy;

class TrackingPixelProcessor extends EmailContentDecorator
{
    /**
     * The tracking strategy to use
     */
    protected TrackingStrategy $trackingStrategy;

    /**
     * Constructor
     *
     * @param  EmailContentProcessorInterface  $processor  The processor to wrap
     * @param  TrackingStrategy  $trackingStrategy  The tracking strategy to use
     */
    public function __construct(
        EmailContentProcessorInterface $processor,
        TrackingStrategy $trackingStrategy
    ) {
        parent::__construct($processor);
        $this->trackingStrategy = $trackingStrategy;
    }

    /**
     * Add tracking pixels to the email content
     *
     * @param  string  $content  The content processed by previous decorators
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The content with tracking pixels added
     */
    protected function applyDecoration(string $content, object $campaign, object $contact): string
    {
        return $this->trackingStrategy->addTrackingToEmail($content, $campaign, $contact);
    }
}
