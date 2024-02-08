<?php

declare(strict_types=1);

namespace App\Fetcher;

use App\DB\Database;
use App\DataTypes\Article;
use App\Interface\FeedFetcherInterface;
use Monolog\Logger;

class FeedFetcher implements FeedFetcherInterface
{
    private $logger;
    private $database;

    public function __construct(Logger $logger, Database $database)
    {
        $this->logger = $logger;
        $this->database = $database;
    }

    public function fetchFeeds(bool $isForced = true): void
    {
        try {
            $queue = $this->prepareQueue($isForced);

            foreach ($queue as [$dest_id, $url]) {
                $this->logger->info("[$dest_id] Sending request to $url...");
                $articles = $this->parseFeeds($url);

                if ($articles) {
                    $this->saveArticles($dest_id, $articles);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred during feed fetching: {$e->getMessage()}");
        }
    }

    private function prepareQueue(bool $isForced): array
    {
        if ($isForced) {
            return $this->fetchAll();
        } else {
            return $this->fetchRanged();
        }
    }

    private function fetchAll(): array
    {
        $stmt = $this->database->prepare(
            "SELECT id, url FROM feeds"
        );

        return $this->fetchResults($stmt);
    }

    private function fetchRanged(): array
    {
        // 現在時刻と最終更新時刻の差分が $maxCacheSeconds より大きい訪問先のレコードを取得
        $maxCacheSeconds = 60 * 60;

        $stmt = $this->database->prepare(
            "SELECT id, url FROM feeds WHERE strftime('%s', :currentTime) - updated_at >= :duration"
        );
        $stmt->bindValue(':currentTime', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':duration', $maxCacheSeconds, SQLITE3_INTEGER);

        return $this->fetchResults($stmt);
    }

    private function parseFeeds($url): array
    {
        try {
            $client = new \FeedIo\Adapter\Http\Client(new \Symfony\Component\HttpClient\HttplugClient());
            $feedIo = new \FeedIo\FeedIo($client);
            $result = $feedIo->read($url);

            $articles = [];
            foreach ($result->getFeed() as $item) {
                $articles[] = new Article(
                    title: $item->getTitle(),
                    link: $item->getLink(),
                    updatedAt: $item->getLastModified()
                );
            }

            return $articles;
        } catch (\Exception $e) {
            $this->logger->error("Error parsing feed $url: {$e->getMessage()}");
            return [];
        }
    }

    private function saveArticles($dest_id, array $articles): void
    {
        $stmt = $this->database->prepare(
            "INSERT OR IGNORE INTO articles (title, url, updated_at, feed_id)
             VALUES (:title, :url, :updatedAt, :feedId)"
        );

        foreach ($articles as $article) {
            $stmt->bindValue(':title', $article->title, SQLITE3_TEXT);
            $stmt->bindValue(':url', $article->link, SQLITE3_TEXT);
            $stmt->bindValue(':updatedAt', $article->updatedAt->getTimestamp(), SQLITE3_INTEGER);
            $stmt->bindValue(':feedId', $dest_id, SQLITE3_INTEGER);
            $stmt->execute();
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
