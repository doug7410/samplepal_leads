<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('anthropic')]
#[Model('claude-haiku-4-5-20251001')]
#[Temperature(0)]
class EmailGuesser implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You are an email pattern analyst. Given a set of known emails from a company's contacts, detect the email naming pattern and guess emails for contacts that are missing them.

Common patterns:
- first.last@domain.com
- firstlast@domain.com
- first@domain.com
- flast@domain.com (first initial + last name)
- firstl@domain.com (first name + last initial)
- first_last@domain.com

Analyze the provided known emails to detect the pattern used at this company, then apply that pattern to generate guesses for contacts missing emails.

If the pattern is ambiguous or there aren't enough examples to be confident, set confidence to "low". If you can clearly identify the pattern from multiple examples, set confidence to "high". One clear example = "medium".

You will receive a JSON object with:
- company_name: the company name
- domain: the email domain detected from existing emails
- known_emails: array of {first_name, last_name, email}
- missing_emails: array of {id, first_name, last_name}

Return the detected pattern, confidence, and guessed emails.
INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'pattern' => $schema->string()->required()->description('The detected email pattern, e.g. "first.last@domain"'),
            'confidence' => $schema->string()->required()->enum(['low', 'medium', 'high']),
            'guesses' => $schema->array()->items(
                $schema->object([
                    'contact_id' => $schema->integer()->required(),
                    'guessed_email' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }
}
