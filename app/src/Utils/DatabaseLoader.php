<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config;
use Monolog\Logger;
use App\Data\CommandFlags\Flags;

/**
 * A class that applies the content of command-line flags, and returns PDO instance.
 */
class DatabaseLoader
{
    private Logger $logger;
    private Flags $flags;

    public function __construct(Logger $logger, Flags $flags)
    {
        $this->flags = $flags;
        $this->logger = $logger;
        $this->logger->debug("DatabaseLoader ready", ['flags' => (array)$flags]);
    }

    public function create(): \PDO
    {
        $databasePath = !empty($this->flags->getDatabasePath()) ? $this->flags->getDatabasePath() : Config::DATABASE_PATH;

        if (!file_exists($databasePath)) {
            $this->logger->info("Database not found. creating a dedicated database...", ['path' => $databasePath]);
        } else {
            $this->logger->info("Database found.", ['path' => $databasePath]);
        }

        return new \PDO("sqlite:$databasePath");
    }
}
