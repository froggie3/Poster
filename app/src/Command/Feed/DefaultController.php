<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Config;
use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Feed;
use DatabaseLoader;
use Minicli\Command\CommandController;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// command-line flags and parameters
const FLAG_FORCE_UPDATE = 'force-update';
const FLAG_NO_UPDATE_CHECK = 'no-update-check';
const PARAM_DATABASE_PATH = 'database-path';

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $flags = new Flags();

        if ($this->hasParam(PARAM_DATABASE_PATH)) {
            $databasePath = $this->getParam(PARAM_DATABASE_PATH);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(FLAG_FORCE_UPDATE));
        }

        if ($this->hasFlag(FLAG_NO_UPDATE_CHECK)) {
            $flags = $flags->setUpdateSkipped($this->hasFlag(FLAG_NO_UPDATE_CHECK));
        }

        $logger = new Logger("Feed", [
            new StreamHandler(CONFIG::LOGGING_PATH, Config::MONOLOG_LOG_LEVEL),
        ]);

        $feed = new Feed(
            $logger,
            $flags,
            (new \App\Utils\DatabaseLoader($logger, $flags))->create()
        );

        $feed->process();
    }
}
