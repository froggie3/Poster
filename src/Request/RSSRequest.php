<?php

declare(strict_types=1);

namespace App\Request;

class RSSRequest extends Request
{
    private string $feedUrl;

    public function __construct(string $feedUrl)
    {
        $this->feedUrl = $feedUrl;
        parent::__construct();
    }

    public function fetch(): string | false 
    {
        $response = $this->get($this->feedUrl);
        if (!$response) {
            return false;
        }
        try {
            $Data = $response;
        } catch (\JsonException $e) {
            echo $e->getMessage() . "\n";
            return false;
        }
        return $Data;
    }
}
