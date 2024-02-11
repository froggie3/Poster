<?php

declare(strict_types=1);

namespace App\Command\Feed;

use \App\Domain\Feed\Feed;
use Minicli\Command\CommandController;
use \App\DB\Database;
use Monolog\Logger;
use \App\Config;
use \App\Constants;
use \App\Data\CommandFlags\FeedFetcherFlags;
use \App\Fetcher\FeedFetcher;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\StreamHandler;

// command-line flags and parameters
const FLAG_FORCE_UPDATE = 'force-update';
const PARAM_DATABASE_DIR = 'database-dir';

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $flags = new FeedFetcherFlags();

        if ($this->hasParam(PARAM_DATABASE_DIR)) {
            $databasePath = $this->getParam(PARAM_DATABASE_DIR);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(FLAG_FORCE_UPDATE));
        }

        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $logger = new Logger("Feed", [
            new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler(),
        ]);
        $feed = new Feed($logger, $flags);
        $feed->process();
    }
}
