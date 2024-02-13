<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Config;
use App\Data\CommandFlags\FeedFetcherFlags;
use App\DB\Database;
use Monolog\Logger;
use App\Utils\ClientFactory;

class ProvidersRetriever
{
    private Logger $logger;
    private Database $db;
    private FeedFetcherFlags $flags;

    private array $queue = [];

    public function __construct(Logger $logger, Database $db, FeedFetcherFlags $flags)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->db = $db;
    }


    /** 訪問するべきフィード発信元を追加 */
    public function fetch(): array
    {
        try {
            if ($this->flags->isForced()) {
                $this->logger->alert("'--force-update' is set");
            }

            $stmt = $this->db->prepare(
                $this->flags->isForced()
                    ? "SELECT id, url FROM feeds"
                    : "SELECT id, url FROM feeds 
                   WHERE strftime('%s', 'now') - updated_at >= :cache"
            );

            if ($stmt) {
                if (!$this->flags->isForced()) {
                    $stmt->bindValue(':cache', Config::FEED_CACHE_LIFETIME, SQLITE3_INTEGER);
                }

                $result = $stmt->execute();

                if ($result) {
                    $feedIo = new \FeedIo\FeedIo(
                        new \App\FeedIo\Adapter\Http\Client(
                            (new ClientFactory($this->logger))->create()
                        ),
                        $this->logger
                    );
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $this->queue[] = new Website($feedIo, ...$row, logger: $this->logger,);
                    }
                    // print_r($this->queue);
                    // exit;
                } else {
                    throw new \SQLite3Exception("Failure on fetching results");
                }
            } else {
                throw new \SQLite3Exception("Failure on preparing statements");
            }
        } catch (\SQLite3Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this->queue;
    }
}
