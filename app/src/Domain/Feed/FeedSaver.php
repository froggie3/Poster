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

    /** 既存の記事とダウンロードした記事を重複しないようにデータベースに挿入 */
    public function save(): void
    {
        foreach ($this->feedProvider->getArticles() as $article) {
            $this->insertArticle($article);
        }
    }

    private function insertArticle(Article $article)
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO articles (title, url, updated_at, created_at, feed_id)
            VALUES (:title, :url, :updatedAt, strftime('%s', 'now'), :feedId)"
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
