<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/./Config/Config.php';

use App\Fetcher\ForcastFetcher;
use Exception;
use Monolog\{Level, Logger, Handler\StreamHandler, Handler\ErrorLogHandler,};
use const \Config\{WEBHOOK_URL_KEY, PLACE_ID_KEY};

$logger = new Logger("ForcastFetcher", [
    new StreamHandler(__DIR__ . "/../logs/app.log", Level::Info),
    new ErrorLogHandler(level: Level::Info),
]);

try {
    if (!$placeId = getenv(PLACE_ID_KEY))
        throw new Exception("Environment variable '" . PLACE_ID_KEY . "' is not set");

    if (gettype($placeId) !== 'string')
        throw new Exception("Environment variable '" . PLACE_ID_KEY . "' must be string");

    if (!$webhookUrl = getenv(WEBHOOK_URL_KEY))
        throw new Exception("Environment variable '" . WEBHOOK_URL_KEY . "' is not set");

    if (gettype($webhookUrl) !== 'string')
        throw new Exception("Environment variable '" . WEBHOOK_URL_KEY . "' must be string");
} catch (Exception $e) {
    $logger->error($e->getMessage());
    return 1;
}

$ff = new ForcastFetcher($logger, $webhookUrl);

$logger->info("Location UID to get: '$placeId'");
$ff->addQueue($placeId);

$ff->fetchForecast();
