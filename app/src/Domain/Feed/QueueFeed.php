<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Config;
use App\Data\CommandFlags\FeedFetcherFlags;
use App\DB\Database;
use Monolog\Logger;


class QueueFeed
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

        $this->logger->info('FeedQueue initialized');
    }

    /** 訪問するべきフィード発信元を追加 */
    public function fetch(): void
    {
        try {
            $this->queue = $this->prepareQueue();
        } catch (\Exception $e) {
            $this->logger->error("An error occurred during feed fetching: {$e->getMessage()}");
        }

        if ($this->needsUpdate()) {
            $this->logger->info("No needs to update");
            return;
        }
        $this->logger->info("Update needed", ['count' => count($this->queue)]);
    }

    public function needsUpdate(): bool { return !empty($this->queue); }

    public function getProviders(): array { return $this->queue; }

    private function prepareQueue(): array
    {
        if ($this->flags->isForced()) {
            $this->logger->info("force-update flag is set");
            return $this->fetchAll();
        } else {
            return $this->fetchRanged();
        }
    }

    /** Visit all the feed providers regardless the last update time */
    private function fetchAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, url FROM feeds"
        );

        return $this->fetchResults($stmt);
    }

    /** 現在時刻と最終更新時刻の差分が $maxCacheSeconds より大きい訪問先のレコードを取得 */
    private function fetchRanged($maxCacheSeconds = Config::FEED_UPDATE_LAZYNESS_SECONDS): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, url FROM feeds WHERE strftime('%s', 'now') - updated_at >= :duration"
        );
        $stmt->bindValue(':duration', $maxCacheSeconds, SQLITE3_INTEGER);

        return $this->fetchResults($stmt);
    }

    private function fetchResults(\SQLite3Stmt $stmt): array
    {
        $result = $stmt->execute();
        $results = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $results[] = array_values($row);
        }

        return $results;
    }
}
