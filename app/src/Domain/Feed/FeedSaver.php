<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\DB\Database;
use Monolog\Logger;

class FeedSaver
{
    private Database $db;
    private Logger $logger;
    private Website $feedProvider;

    public function __construct(Logger $logger, Database $db, Website $feedProvider)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->feedProvider = $feedProvider;

        $this->logger->info('FeedSaver initialized');
    }

    public function save(): void
    {
        foreach ($this->feedProvider->getArticles() as $article) {
            $this->insertArticle($article);
        }
    }

    private function insertArticle(Article $article)
    {
        // もし同じURLを持つ既存の記事があった場合は、なにもしない
        $stmt = $this->db->prepare(
            "INSERT INTO articles (title, url, feed_id, updated_at)
            VALUES (:title, :url, :feedId, :updatedAt)
            ON CONFLICT (url) DO NOTHING"
        );

        if ($stmt !== false) {
            foreach ([
                [':title', $article->title, SQLITE3_TEXT],
                [':url', $article->link, SQLITE3_TEXT],
                [':updatedAt', $article->updatedAt->getTimestamp(), SQLITE3_INTEGER],
                [':feedId', $this->feedProvider->getId(), SQLITE3_INTEGER],
            ] as $args) {
                if (!$stmt->bindValue(...$args)) {
                    throw new \Exception("Error while binding values");
                }
            }
            $this->logger->info($stmt->getSQL(true));

            if ($stmt->execute() === false) {
                throw new \Exception("Error while executing query");
            }
        } else {
            throw new \Exception("Error while building statement");
        }
    }
}
