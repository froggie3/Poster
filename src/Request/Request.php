<?php

declare(strict_types=1);

namespace App\Request;

class Request
{
    protected array $headers;
    protected string $url;

    public function __construct()
    {
        $this->headers = [
            "User-Agent: Mozilla/5.0"
        ];
    }

    protected function post($url, $payload): string | false
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $this->headers),
                'content' => $payload
            ]
        ]);
        $response = file_get_contents($url, false, $context);
        return $response;
    }

    protected function get(string $url, array $queries = []): string | false
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $this->headers),
            ]
        ]);
        // no problem since even it's empty it returns empty string
        $url .= http_build_query($queries);
        $response = file_get_contents($url, false, $context);

        // add a custom exception handler sometime
        if (!$response) {
            return false;
        }

        return $response;
    }
}
