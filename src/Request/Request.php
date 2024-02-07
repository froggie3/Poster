<?php

declare(strict_types=1);

namespace App\Request;

interface RequestTemplate
{
    public function post(string $url, string $payload): string | false;
    public function get(string $url, array $queries = []): string | false;
}

class Request implements RequestTemplate
{
    public array $headers;
    public array $queries;
    public string $url;

    public function __construct()
    {
        $this->headers = [
            "User-Agent: Mozilla/5.0"
        ];
    }

    public function get(string $url, array $queries = []): string | false
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
        return $response;
    }

    public function post(string $url, string $payload): string | false
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
}
