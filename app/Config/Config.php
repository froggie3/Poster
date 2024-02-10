<?php

declare(strict_types=1);

namespace App;

class Config
{
    const MAX_REQUEST_RETRY = 5;
    const INTERVAL_REQUEST  = 2;
    const CONNECTION_TIMEOUT = 2;
    const MONOLOG_LOG_LEVEL = \Monolog\Level::Debug;
}
