<?php

declare(strict_types=1);

namespace App;

class Config
{
    public const CONNECTION_TIMEOUT_SECONDS = 2;
    public const INTERVAL_REQUEST_SECONDS = 2;
    public const FEED_UPDATE_LAZYNESS_SECONDS = 60 * 60 * 24 * 7;
    public const MAX_REQUEST_RETRY = 5;
    public const MONOLOG_LOG_LEVEL = \Monolog\Level::Debug;
}
