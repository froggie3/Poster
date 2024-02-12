<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\DB\Database;
use Monolog\Logger;

class FeedSaver
{
    private Database $database;
    private Logger $logger;
    private array $articles;

    public function __construct(Logger $logger, Database $database, array $articles)
    {
        $this->articles = $articles;
        $this->database = $database;
        $this->logger = $logger;

        $this->logger->info('FeedSaver initialized');
    }

    /** ダウンロードした記事をデータベースに挿入 */
    public function save(): void
    {
        foreach ($this->articles as $destId => $articles) {
            $this->saveByDestId($destId, $articles);
            $this->logger->info('Saved articles from the websites subscribed', [
                'destId' => $destId,
                'count'  => count($articles)
            ]);
        }
    }

    /** 既存の記事とダウンロードした記事を重複しないようにデータベースに挿入 */
    private function saveByDestId($destId, array $articles): void
    {
        $stmt = $this->database->prepare(
            "INSERT OR IGNORE INTO articles (title, url, updated_at, feed_id)
             VALUES (:title, :url, :updatedAt, :feedId)"
        );

        foreach ($articles as $article) {
            $stmt->bindValue(':title', $article->title, SQLITE3_TEXT);
            $stmt->bindValue(':url', $article->link, SQLITE3_TEXT);
            $stmt->bindValue(':updatedAt', $article->updatedAt->getTimestamp(), SQLITE3_INTEGER);
            $stmt->bindValue(':feedId', $destId, SQLITE3_INTEGER);

            $stmt->execute();
        }
    }
}
