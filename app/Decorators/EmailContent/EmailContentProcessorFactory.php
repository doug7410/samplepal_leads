<?php

namespace App\Decorators\EmailContent;

use App\Strategies\EmailTracking\TrackingStrategy;

class EmailContentProcessorFactory
{
    /**
     * Create a fully decorated email content processor with all available processors
     *
     * @param  TrackingStrategy  $trackingStrategy  The tracking strategy to use
     */
    public static function createFullProcessor(TrackingStrategy $trackingStrategy): EmailContentProcessorInterface
    {
        // Start with the base processor
        $processor = new BaseEmailContentProcessor;

        // Add the HTML sanitizer first to ensure proper HTML structure
        $processor = new HtmlSanitizerProcessor($processor);

        // Add the template variable processor
        $processor = new TemplateVariableProcessor($processor);

        // Add the tracking pixel processor
        $processor = new TrackingPixelProcessor($processor, $trackingStrategy);

        // Add the footer processor last
        $processor = new FooterProcessor($processor);

        return $processor;
    }

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
        $processor = new TemplateVariableProcessor($processor);

        return $processor;
    }

    /**
     * Create a custom processor with the specified decorators
     *
     * @param  array  $decorators  List of decorator class names to apply
     * @param  array  $dependencies  Dependencies for decorators
     */
    public static function createCustomProcessor(array $decorators, array $dependencies = []): EmailContentProcessorInterface
    {
        // Start with the base processor
        $processor = new BaseEmailContentProcessor;

        // Apply each decorator in the specified order
        foreach ($decorators as $decoratorClass) {
            if (class_exists($decoratorClass)) {
                // If the decorator needs dependencies, use them
                if (method_exists($decoratorClass, '__construct') && count((new \ReflectionClass($decoratorClass))->getConstructor()->getParameters()) > 1) {
                    // Get the first parameter type
                    $firstParam = (new \ReflectionClass($decoratorClass))->getConstructor()->getParameters()[1];
                    $paramType = $firstParam->getType()->getName();

                    // Find the dependency in the provided dependencies array
                    $dependency = null;
                    foreach ($dependencies as $dep) {
                        if ($dep instanceof $paramType) {
                            $dependency = $dep;
                            break;
                        }
                    }

                    if ($dependency) {
                        $processor = new $decoratorClass($processor, $dependency);
                    } else {
                        // Skip this decorator if the required dependency was not provided
                        continue;
                    }
                } else {
                    // Simple decorator with no additional dependencies
                    $processor = new $decoratorClass($processor);
                }
            }
        }

        return $processor;
    }
}
