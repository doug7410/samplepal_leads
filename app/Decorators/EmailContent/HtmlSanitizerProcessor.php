<?php

namespace App\Decorators\EmailContent;

class HtmlSanitizerProcessor extends EmailContentDecorator
{
    /**
     * Sanitize the HTML content to ensure it's well-formed
     *
     * @param  string  $content  The content processed by previous decorators
     * @param  object  $campaign  The campaign being processed
     * @param  object  $contact  The contact receiving the email
     * @return string The sanitized content
     */
    protected function applyDecoration(string $content, object $campaign, object $contact): string
    {
        // Check if the content has HTML tags
        if (! preg_match('/<[^>]+>/', $content)) {
            // Wrap plain text in HTML
            return $this->wrapPlainTextInHtml($content);
        }

        // Make sure we have a proper HTML structure
        if (! preg_match('/<html[^>]*>/i', $content)) {
            $content = $this->ensureProperHtmlStructure($content);
        }

        return $content;
    }

    /**
     * Wrap plain text in HTML structure
     */
    protected function wrapPlainTextInHtml(string $content): string
    {
        $lines = explode("\n", $content);
        $lines = array_map(function ($line) {
            return empty(trim($line)) ? '<br>' : '<p>'.htmlspecialchars($line).'</p>';
        }, $lines);

        $bodyContent = implode("\n", $lines);

        return $this->getHtmlTemplate($bodyContent);
    }

    /**
     * Ensure the content has a proper HTML structure
     */
    protected function ensureProperHtmlStructure(string $content): string
    {
        // If we have a body tag but no html tag, wrap it properly
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $matches)) {
            $bodyContent = $matches[1];

            return $this->getHtmlTemplate($bodyContent);
        }

        // If no body tag but has some HTML, wrap it in body and html
        return $this->getHtmlTemplate($content);
    }

    /**
     * Get a standard HTML template with the given content in the body
     */
    protected function getHtmlTemplate(string $bodyContent): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    '.$bodyContent.'
</body>
</html>';
    }
}
