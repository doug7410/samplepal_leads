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
class JobCategoryClassifier implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You are classifying job titles from lighting industry contacts into exactly 4 categories, or null if the title doesn't clearly fit.

Categories:
- "Principal": Owners, Presidents, VPs, C-suite, Partners, Founders, General Managers. Any VP-level or above regardless of department.
- "Sales": Specification sales, contractor sales, outside/inside sales, business development, sales managers, account managers/executives focused on sales.
- "Operations": Accounting, admin, warehouse, customer service, quotations, purchasing, office managers, operations managers.
- "Project Manager": Project management roles, project coordinators, project engineers focused on PM.
- null: Controls-only roles (unless combined with sales or VP+), marketing-only roles, technical-only roles (engineers, designers without management), vague titles.

Key disambiguation rules:
- "Controls" alone = null (not Sales, not Operations)
- "Controls Manager / Specification Sales" = "Sales" (the sales part wins)
- "VP of Controls" = "Principal" (VP-level = Principal regardless of department)
- "Quotations Manager" = "Operations"
- "Marketing Director" = null (not sales unless title also says sales)
- "Business Development" = "Sales"
- "Inside Sales" or "Outside Sales" = "Sales"
- "Lighting Designer" = null (technical role)
- "Branch Manager" = "Principal"

You will receive a JSON array of contacts with id and job_title. Return the classification for each.
INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'classifications' => $schema->array()->items(
                $schema->object([
                    'id' => $schema->integer()->required(),
                    'category' => $schema->string()->nullable()->enum(['Sales', 'Operations', 'Principal', 'Project Manager']),
                ])
            )->required(),
        ];
    }
}
