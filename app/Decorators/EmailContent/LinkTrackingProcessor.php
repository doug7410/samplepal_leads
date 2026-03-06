<?php

namespace App\Decorators\EmailContent;

class LinkTrackingProcessor extends EmailContentDecorator
{
    protected function applyDecoration(string $content, object $campaign, object $contact): string
    {
        $ref = $campaign->id.'_'.$contact->id;

        return preg_replace_callback(
            '/href="([^"]+)"/i',
            function (array $matches) use ($ref): string {
                $url = $matches[1];

                if (
                    str_starts_with($url, 'mailto:') ||
                    str_starts_with($url, 'tel:') ||
                    str_starts_with($url, '#') ||
                    str_contains($url, 'ref=')
                ) {
                    return $matches[0];
                }

                $separator = str_contains($url, '?') ? '&' : '?';

                return 'href="'.$url.$separator.'ref='.$ref.'"';
            },
            $content
        );
    }
}
