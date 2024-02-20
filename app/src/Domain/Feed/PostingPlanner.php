<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\DB\Database;
use Monolog\Logger;

class PostingPlanner
{
    private Logger $logger;
    private Database $database;
    private array $queue = [];

    public function __construct(Logger $logger, Database $database)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /** これから投稿する記事のURLと投稿先とのペアが post_history テーブルの中になければ取得 */
    public function fetch(): array
    {
        $stmt = $this->database->prepare(
            "SELECT
                articles.id AS articleId,
                articles.title AS articleTitle,
                articles.url AS articleUrl,
                webhooks.id AS webhookId,
                webhooks.title AS webhookTitle,
                webhooks.url AS webhookUrl
            FROM
                articles
                INNER JOIN webhook_map ON webhook_map.webhook_id = webhooks.id
                AND webhook_map.feed_id = articles.feed_id
                INNER JOIN feeds ON feeds.id = webhook_map.feed_id
                AND feeds.id = articles.feed_id
                INNER JOIN webhooks ON webhooks.id = webhook_map.webhook_id
                LEFT JOIN post_history ON post_history.article_id = articles.id
            AND post_history.webhook_id = webhooks.id
            WHERE
                post_history.article_id IS NULL
                AND post_history.webhook_id IS NULL
            ORDER BY
                webhook_map.webhook_id,
                articles.updated_at;"
        );


        if ($stmt) {
            $result = $stmt->execute();
            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $this->queue[] = new PostDto($row);
                }
                return $this->queue;
            } else {
                throw new \Exception('An error occurred during feed fetching');
            }
        } else {
            throw new \Exception('An error occurred while building query');
        }
    }
}
