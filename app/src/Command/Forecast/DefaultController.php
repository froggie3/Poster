<?php

declare(strict_types=1);

namespace App\Command\Forecast;

use App\Config;
use App\Constants;
use App\Domain\Forecast\Forecast;
use Minicli\Command\CommandController;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $loggingPath = __DIR__ . '/../../../logs/app.log';

        $logHandlers = [new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()];
        $logger = new Logger("Forecast", $logHandlers);

        try {
            if (!$placeId = getenv(Constants::PLACE_ID_KEY))
                throw new \Exception("Environment variable '" . Constants::PLACE_ID_KEY . "' is not set");

            if (gettype($placeId) !== 'string')
                throw new \Exception("Environment variable '" . Constants::PLACE_ID_KEY . "' must be string");

            if (!$webhookUrl = getenv(Constants::WEBHOOK_URL_KEY))
                throw new \Exception("Environment variable '" . Constants::WEBHOOK_URL_KEY . "' is not set");

            if (gettype($webhookUrl) !== 'string')
                throw new \Exception("Environment variable '" . Constants::WEBHOOK_URL_KEY . "' must be string");
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            return;
        }

        $forecast = new Forecast($logger, $placeId, $webhookUrl);
        $forecast->process();
    }
}
