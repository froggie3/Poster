<?php

declare(strict_types=1);

namespace App\Domain\Feed\Planner;

use Monolog\Logger;

class PostingPlanner
{
    private Logger $logger;
    private \PDO $pdo;
    private array $queue = [];

    public function __construct(Logger $logger, \PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /** これから投稿する記事のURLと投稿先とのペアが post_history_feed テーブルの中になければ取得 */
    public function fetch(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                articles.id AS articleId,
                articles.title AS articleTitle,
                articles.url AS articleUrl,
                webhooks.id AS webhookId,
                webhooks.title AS webhookTitle,
                webhooks.url AS webhookUrl
            FROM
                articles
                INNER JOIN webhook_map_feed ON webhook_map_feed.webhook_id = webhooks.id
                AND webhook_map_feed.feed_id = articles.feed_id
                INNER JOIN feeds ON feeds.id = webhook_map_feed.feed_id
                AND feeds.id = articles.feed_id
                INNER JOIN webhooks ON webhooks.id = webhook_map_feed.webhook_id
                LEFT JOIN post_history_feed ON post_history_feed.article_id = articles.id
            AND post_history_feed.webhook_id = webhooks.id
            WHERE
                post_history_feed.article_id IS NULL
                AND post_history_feed.webhook_id IS NULL
                AND webhook_map_feed.enabled = 1
            ORDER BY
                webhook_map_feed.webhook_id,
                articles.updated_at;"
        );

        $stmt->execute();

        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $this->queue[] = new PostDto($row);
            }
            return $this->queue;
        } else {
            throw new \Exception('An error occurred while building query');
        }
    }
}
