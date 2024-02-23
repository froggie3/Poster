<?php

declare(strict_types=1);

namespace App\Domain\Feed\Updater\Retriever;

use App\Config;
use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Updater\Retriever\Website\Website;
use App\Utils\ClientFactory;
use Monolog\Logger;

class ProvidersRetriever
{
    private Logger $logger;
    private \PDO $db;
    private Flags $flags;
    private array $queue = [];

    public function __construct(
        Logger $logger,
        \PDO $db,
        Flags $flags
    ) {
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
                    : "SELECT id, url FROM feeds WHERE strftime('%s', 'now') - updated_at >= :cache"
            );

            if ($stmt) {
                if (!$this->flags->isForced()) {
                    $stmt->bindValue(':cache', Config::FEED_CACHE_LIFETIME, \PDO::PARAM_INT);
                }

                $feedIo = new \FeedIo\FeedIo(
                    new \App\FeedIo\Adapter\Http\Client(
                        (new ClientFactory($this->logger))->create()
                    ),
                    $this->logger
                );

                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $this->queue[] = new Website(
                        $feedIo,
                        ...$row,
                        logger: $this->logger,
                    );
                }
                // print_r($this->queue);
                // exit;
            } else {
                throw new \PDOException("Failure on preparing statements");
            }
        } catch (\PDOException $e) {
            $this->logger->error($e->getMessage());
        }
        return $this->queue;
    }
}
