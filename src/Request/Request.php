<?php

namespace App\Request;

class Request
{
    protected $headers;
    protected $url;

    public function __construct()
    {
        $this->headers = [
            "User-Agent: Mozilla/5.0"
        ];
    }

    protected function post($url, $payload)
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

    protected function get($url, $queries = [])
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $this->headers),
            ]
        ]);
        // no problem since even it's empty it returns empty string
        $url .= http_build_query($queries);
        if ($response = file_get_contents($url, false, $context)) {
            return $response;
        }
        return "";
    }
}