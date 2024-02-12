<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\DB\Database;
use Monolog\Logger;

class QueuePost
{
    private Logger $logger;
    private Database $database;
    private array $queue = [];

    public function __construct(Logger $logger, Database $database)
    {
        $this->database = $database;
        $this->logger = $logger;

        $this->logger->info('PostQueue initialized');
    }

    public function fetch(): array
    {
        return $this->queue;
    }

    /** これから投稿する記事のURLと投稿先とのペアが post_history テーブルの中になければ取得 */
    public function process(): void 
    {
        $stmt = $this->extractUnposted();
        try {
            $this->queue = $this->fetchResults($stmt);
        } catch (\Exception $e) {
            $this->logger->error("An error occurred during feed fetching: {$e->getMessage()}");
        }
    }


    private function extractUnposted(): \SQLite3Stmt
    {
        try {
            return
                $this->database->prepare(
                    "SELECT
                        a.id AS article_id,
                        a.title AS article_title,
                        a.url AS article_url,
                        w.id AS webhook_id,
                        w.title AS webhook_title,
                        w.url AS webhook_url
                    FROM
                        articles a
                    INNER JOIN
                        webhooks w ON w.source_id = 2
                    LEFT JOIN
                        post_history ph ON ph.article_id = a.id
                        AND ph.webhook_id = w.id
                    WHERE
                        ph.article_id IS NULL AND ph.webhook_id IS NULL"
                );
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while building query: {$e->getMessage()}");
        }
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
