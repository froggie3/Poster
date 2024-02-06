<?php

declare(strict_types=1);

namespace App\DataTypes;


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
