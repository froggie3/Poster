<?php

declare(strict_types=1);

namespace App\Command\Feed;

use \App\Constants;
use \App\Config;
use Minicli\Command\CommandController;
use \App\DB\Database;
use \App\Fetcher\FeedFetcher;
use \Monolog\{Logger, Handler\StreamHandler, Handler\ErrorLogHandler,};

// command-line flags and parameters
const  FLAG_FORCE_UPDATE = 'force-update';
const PARAM_DATABASE_DIR = 'database-dir';

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $databasePath = $this->hasParam(PARAM_DATABASE_DIR)
            ? $this->getParam(PARAM_DATABASE_DIR)
            : __DIR__ . '/../../../../sqlite.db';

        $loggingPath = __DIR__ . '/../../../logs/app.log';

        if ($this->hasFlag(FLAG_FORCE_UPDATE)) {
            // 直ちに最新のフィードを取得する場合の処理
            $this->display("FLAG_FORCE_UPDATE on");
        }

        $db = new Database($databasePath);
        $logger = new Logger(Constants::MODULE_FEED_FETCHER, [
            new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL),
            new ErrorLogHandler(),
        ]);

        $fetcher = new FeedFetcher($logger, $db);
        $fetcher->fetchFeeds();
    }
}
