<?php

declare(strict_types=1);

namespace App\Interface;

interface FeedFetcherInterface
{
    public function fetchFeeds(bool $isForced = false): void;
}
