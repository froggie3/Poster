<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use \App\Config;
use \App\Constants;
use \App\Data\CommandFlags\FeedFetcherFlags;
use \App\DB\Database;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\StreamHandler;
use \Monolog\Logger;

class Feed
{
    private array $logHandlers;
    private Database $db;
    private FeedFetcherFlags $flags;
    private Logger $logger;
    private string $loggingPath;

    public function __construct(Logger $logger, FeedFetcherFlags $flags)
    {
        $this->flags = $flags;
        $this->logger = $logger;
        if (empty($flags->getDatabasePath())) {
            $this->db = new Database(__DIR__ . '/../../../../sqlite.db');
        }
        $this->logger->debug("Feed got ready", ['flags' => $flags]);
    }

    public function process()
    {
        $this->loggingPath = __DIR__ . '/../../../logs/app.log';
        $this->logHandlers = [
            new StreamHandler($this->loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()
        ];
        $logger = new Logger(Constants::MODULE_FEED_FETCHER, $this->logHandlers);
        $fetcher = new FeedFetcher($logger, $this->db, $this->flags);
        $fetcher->fetch();
    }
}
