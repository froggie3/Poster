<?php

declare(strict_types=1);

namespace App\Command\Forecast;

use App\Config;
use App\Constants;
use App\Domain\Forecast\Forecaster;
use Minicli\Command\CommandController;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use App\Data\CommandFlags\Flags;

const FLAG_FORCE_UPDATE = 'force-update';
const PARAM_DATABASE_PATH = 'database-path';

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $logHandlers = [new StreamHandler(CONFIG::LOGGING_PATH, Config::MONOLOG_LOG_LEVEL)];
        $logger = new Logger("Forecast", $logHandlers);
        $flags = new Flags();

        if ($this->hasParam(PARAM_DATABASE_PATH)) {
            $databasePath = $this->getParam(PARAM_DATABASE_PATH);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(FLAG_FORCE_UPDATE));
        }

        $forecast = new Forecaster(
            $logger,
            $flags,
            (new \App\Utils\DatabaseLoader($logger, $flags))->create()
        );

        $forecast->process();
    }
}
