<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Config;
use App\Data\CommandFlags\FeedFetcherFlags;
use App\Domain\Feed\Feed;
use Minicli\Command\CommandController;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// command-line flags and parameters
const FLAG_FORCE_UPDATE = 'force-update';
const PARAM_DATABASE_PATH = 'database-path';

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $flags = new FeedFetcherFlags();

        if ($this->hasParam(PARAM_DATABASE_PATH)) {
            $databasePath = $this->getParam(PARAM_DATABASE_PATH);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(FLAG_FORCE_UPDATE));
            //var_dump($flags);
        }

        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $logger = new Logger("Feed", [
            new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler(),
        ]);
        $feed = new Feed($logger, $flags);
        $feed->process();
    }
}
