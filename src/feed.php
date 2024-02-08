<?php

declare(strict_types=1);

namespace App;

use App\DB\Database;
use App\Fetcher\FeedFetcher;

use Monolog\{Level, Logger, Handler\StreamHandler, Handler\ErrorLogHandler,};

$db = new Database(__DIR__ . '/../sqlite.db');
$logger = new Logger('FeedFetcher', [
    new StreamHandler(__DIR__ . '/../logs/app.log', Level::Debug),
    new ErrorLogHandler(),
]);

$fetcher = new FeedFetcher($logger, $db);
$fetcher->fetchFeeds();
