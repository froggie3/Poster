<?php

declare(strict_types=1);

namespace App\Command\Forecast;

use App\Config;
use App\Constants;
use App\Data\CommandFlags\Flags;
use App\Domain\Forecast\Forecaster;
use App\Utils\ClientFactory;
use Minicli\Command\CommandController;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;


class DefaultController extends CommandController
{
    public function handle(): void
    {
        $logHandlers = [new StreamHandler(Config::LOGGING_PATH, Config::MONOLOG_LOG_LEVEL)];
        $logger = new Logger("Forecast", $logHandlers);
        $flags = new Flags();

        if ($this->hasParam(Constants::PARAM_DATABASE_PATH)) {
            $databasePath = $this->getParam(Constants::PARAM_DATABASE_PATH);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(Constants::FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(Constants::FLAG_FORCE_UPDATE));
        }

        $forecast = new Forecaster(
            $logger,
            $flags,
            (new \App\Utils\DatabaseLoader($logger, $flags))->create(),
            (new ClientFactory($logger, ['Content-Type' => 'application/json']))->create(),
        );

        $forecast->process();
    }
}
