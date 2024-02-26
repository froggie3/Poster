<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Config;
use App\Constants;
use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Feed;
use App\Utils\ClientFactory;
use Minicli\Command\CommandController;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $flags = new Flags();

        if ($this->hasParam(Constants::PARAM_DATABASE_PATH)) {
            $databasePath = $this->getParam(Constants::PARAM_DATABASE_PATH);
            $flags = $flags->setDatabasePath($databasePath);
        }

        if ($this->hasFlag(Constants::FLAG_FORCE_UPDATE)) {
            $flags = $flags->setForced($this->hasFlag(Constants::FLAG_FORCE_UPDATE));
        }

        if ($this->hasFlag(Constants::FLAG_NO_UPDATE_CHECK)) {
            $flags = $flags->setUpdateSkipped($this->hasFlag(Constants::FLAG_NO_UPDATE_CHECK));
        }

        $logger = new Logger("Feed", [
            new StreamHandler(CONFIG::LOGGING_PATH, Config::MONOLOG_LOG_LEVEL),
        ]);

        $feed = new Feed(
            $logger,
            $flags,
            (new \App\Utils\DatabaseLoader($logger, $flags))->create(),
            (new ClientFactory($logger, ['Content-Type' => 'application/json']))->create(),
        );

        $feed->process();
    }
}
