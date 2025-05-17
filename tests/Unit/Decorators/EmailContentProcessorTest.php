<?php

namespace Tests\Unit\Decorators;

use App\Decorators\EmailContent\EmailContentProcessorInterface;
use PHPUnit\Framework\TestCase;

class EmailContentProcessorTest extends TestCase
{
    /**
     * Test that the decorator pattern works with a simple implementation
     */
    public function test_decorator_pattern_works_correctly()
    {
        // Create a mock base processor
        $baseProcessor = $this->createMock(EmailContentProcessorInterface::class);
        $baseProcessor->method('process')
            ->willReturn('<p>Base content</p>');

        // Create a simple decorator
        $decorator = new class($baseProcessor) implements EmailContentProcessorInterface
        {
            private $wrappedProcessor;

            public function __construct(EmailContentProcessorInterface $processor)
            {
                $this->wrappedProcessor = $processor;
            }

            public function process(string $content, object $campaign, object $contact): string
            {
                $processed = $this->wrappedProcessor->process($content, $campaign, $contact);

                return $processed.'<p>Decorated content</p>';
            }
        };

        // Create simple test objects
        $campaign = new \stdClass;
        $contact = new \stdClass;

        // Process content
        $result = $decorator->process('Original content', $campaign, $contact);

        // Verify results
        $this->assertStringContainsString('Base content', $result);
        $this->assertStringContainsString('Decorated content', $result);
    }

    /**
     * Test that multiple decorators can be chained
     */
    public function test_multiple_decorators_can_be_chained()
    {
        // Create simple test objects
        $campaign = new \stdClass;
        $contact = new \stdClass;

        // Create a mock base processor
        $baseProcessor = $this->createMock(EmailContentProcessorInterface::class);
        $baseProcessor->method('process')
            ->willReturn('<p>Base content</p>');

        // Create first decorator
        $firstDecorator = new class($baseProcessor) implements EmailContentProcessorInterface
        {
            private $wrappedProcessor;

            public function __construct(EmailContentProcessorInterface $processor)
            {
                $this->wrappedProcessor = $processor;
            }

            public function process(string $content, object $campaign, object $contact): string
            {
                $processed = $this->wrappedProcessor->process($content, $campaign, $contact);

                return $processed.'<p>First decorator</p>';
            }
        };

        // Create second decorator
        $secondDecorator = new class($firstDecorator) implements EmailContentProcessorInterface
        {
            private $wrappedProcessor;

            public function __construct(EmailContentProcessorInterface $processor)
            {
                $this->wrappedProcessor = $processor;
            }

            public function process(string $content, object $campaign, object $contact): string
            {
                $processed = $this->wrappedProcessor->process($content, $campaign, $contact);

                return $processed.'<p>Second decorator</p>';
            }
        };

        // Process content
        $result = $secondDecorator->process('Original content', $campaign, $contact);

        // Verify results
        $this->assertStringContainsString('Base content', $result);
        $this->assertStringContainsString('First decorator', $result);
        $this->assertStringContainsString('Second decorator', $result);

        // Verify order
        $this->assertGreaterThan(
            strpos($result, 'Base content'),
            strpos($result, 'First decorator'),
            'First decorator should be after base content'
        );

        $this->assertGreaterThan(
            strpos($result, 'First decorator'),
            strpos($result, 'Second decorator'),
            'Second decorator should be after first decorator'
        );
    }
}
