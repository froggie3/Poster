<?php

declare(strict_types=1);

namespace App\Domain\Feed;


use Monolog\Logger;

class FeedSaver
{
    private \PDO $db;
    private Logger $logger;
    private Website $feedProvider;

    public function __construct(Logger $logger, \PDO $db, Website $feedProvider)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->feedProvider = $feedProvider;
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
            "INSERT OR IGNORE INTO articles (title, url, feed_id, updated_at)
            VALUES (:title, :url, :feedId, :updatedAt);"
        );

        if ($stmt !== false) {
            foreach ([
                [':title', $article->title, \PDO::PARAM_STR],
                [':url', $article->link, \PDO::PARAM_STR],
                [':updatedAt', $article->updatedAt->getTimestamp(), \PDO::PARAM_INT],
                [':feedId', $this->feedProvider->getId(), \PDO::PARAM_INT],
            ] as $args) {
                if (!$stmt->bindValue(...$args)) {
                    throw new \Exception("Error while binding values");
                }
            }
            // $this->logger->info($stmt->queryString);

            if ($stmt->execute() === false) {
                throw new \Exception("Error while executing query");
            }
        } else {
            throw new \Exception("Error while building statement");
        }
    }
}
