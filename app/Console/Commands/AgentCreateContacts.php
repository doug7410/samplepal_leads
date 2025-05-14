<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class AgentCreateContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:agent-create-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query all companies and display them';

    /**
     * Fix website URLs that are actually email addresses
     */
    private function fixWebsiteUrl(string $website): string
    {
        // Check if the website is actually an email address
        if (str_starts_with($website, 'mailto:')) {
            // Extract domain from email
            preg_match('/mailto:.*@(.+)/', $website, $matches);
            if (isset($matches[1])) {
                return 'https://' . $matches[1];
            }
        }

        // Fix URLs without http/https protocol
        if (!str_starts_with($website, 'http://') && !str_starts_with($website, 'https://')) {
            return 'https://' . $website;
        }

        return $website;
    }

    public function handle(): void
    {
        $company = Company::with('contacts')
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->whereDoesntHave('contacts')
            ->first();

        if (!$company) {
            $this->error('No companies found with websites and no contacts.');
            return;
        }

        $contactResearcherPrompt = file_get_contents(app_path('Prompts/contact_researcher_prompt.txt'));
        $fixedWebsite = $this->fixWebsiteUrl($company->website);

        $personalizedPrompt = str_replace('{website}', $fixedWebsite, $contactResearcherPrompt);

        $this->info("Processing company: {$company->company_name} ({$fixedWebsite})");

        // Write personalized prompt to a temporary file with a stable name
        $promptFilePath = "/tmp/samplepal_prompt.txt";
        file_put_contents($promptFilePath, $personalizedPrompt);

        // Get full path to the claude executable
        $claudePath = '/Users/dougsteinberg/.claude/local/claude';

        // Build the command to run claude with the prompt file and allow all necessary tools
        $allowedTools = "Playwright WebFetch WebSearch Bash Task";
        
        // Create a temporary config file for MCP Playwright
        $mcpConfigPath = "/tmp/samplepal_mcp_config.json";
        $mcpConfig = [
            'browser' => [
                'launchOptions' => [
                    'headless' => false,
                    'channel' => 'chrome'
                ]
            ]
        ];
        file_put_contents($mcpConfigPath, json_encode($mcpConfig, JSON_PRETTY_PRINT));
        
        // Create a complete script with environment variables set properly
        $scriptContent = <<<SCRIPT
#!/bin/bash
export MCP_PLAYWRIGHT_HEADED=1
export CLAUDE_MCP_PLAYWRIGHT_HEADED=1
export MCP_PLAYWRIGHT_CONFIG_PATH="{$mcpConfigPath}"
export PLAYWRIGHT_BROWSER_EXECUTABLE_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
export NODE_TLS_REJECT_UNAUTHORIZED=0
export PLAYWRIGHT_BROWSERS_PATH=0

# Run Claude with the prompt
cat {$promptFilePath} | {$claudePath} --print --output-format text --allowedTools '{$allowedTools}'
SCRIPT;
        
        // Save the full script to a file
        $commandFilePath = "/tmp/samplepal_claude_command.sh";
        file_put_contents($commandFilePath, $scriptContent);
        chmod($commandFilePath, 0755);
        
        // Use bash to execute the script directly
        $claudeCommand = "bash {$commandFilePath}";
        chmod($commandFilePath, 0755);

        $this->info("Command saved to: {$commandFilePath}");
        $this->info("Prompt saved to: {$promptFilePath}");
        $this->info("MCP Config saved to: {$mcpConfigPath}");
        
        // Execute the command
        $this->info("Running command: {$claudeCommand}");
        exec($claudeCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Error running Claude CLI command (code: {$returnCode})");
            return;
        }

        $this->info('Claude response:');
        $this->newLine();
        $this->line(implode("\n", $output));
    }
}
