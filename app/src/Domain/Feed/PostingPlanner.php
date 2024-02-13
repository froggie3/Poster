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
                articles.id    AS articleId,
                articles.title AS articleTitle,
                articles.url   AS articleUrl,
                webhooks.id    AS webhookId,
                webhooks.title AS webhookTitle,
                webhooks.url   AS webhookUrl
            FROM
                articles
            INNER JOIN
                webhooks ON webhooks.source_id = 2
            LEFT JOIN
                post_history ON post_history.article_id = articles.id
                AND post_history.webhook_id = webhooks.id
            WHERE
                post_history.article_id IS NULL AND post_history.webhook_id IS NULL"
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
