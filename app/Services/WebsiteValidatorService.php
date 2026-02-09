<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WebsiteValidatorService
{
    /**
     * Validate whether a website URL is reachable.
     *
     * @return string One of: reachable, unreachable, ssl_error, timeout
     */
    public function validate(string $url): string
    {
        $url = $this->normalizeUrl($url);

        try {
            $response = Http::timeout(10)->connectTimeout(5)->get($url);

            return $response->successful() || $response->redirect() ? 'reachable' : 'unreachable';
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
                return 'ssl_error';
            }

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return 'timeout';
            }

            return 'unreachable';
        }
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }
}
