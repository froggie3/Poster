<?php

declare(strict_types=1);

namespace App\Interface;

interface RequestInterface
{
    public function post(string $url, string $payload): string | false;
    public function get(string $url, array $queries = []): string | false;
}