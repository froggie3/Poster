<?php

declare(strict_types=1);

namespace App\Data;


class FeedStatus
{
    public string $url;
    public bool $status;

    public function __construct(string $url, bool $status)
    {
        $this->url = $url;
        $this->status = $status;
    }
}
