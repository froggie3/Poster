<?php

declare(strict_types=1);

namespace App;

class Config
{
    public const CONNECTION_TIMEOUT_SECONDS = 10;
    public const INTERVAL_REQUEST_SECONDS = 2;
    public const FEED_CACHE_LIFETIME = 60 * 60 * 24 * 7;
    public const MAX_REQUEST_RETRY = 5;
    public const MONOLOG_LOG_LEVEL = \Monolog\Level::Debug;
}
