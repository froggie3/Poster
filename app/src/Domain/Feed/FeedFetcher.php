<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use \App\Config;
use \App\Data\Article;
use \App\Data\CommandFlags\FeedFetcherFlags;
use \App\DB\Database;
use Monolog\Logger;

class FeedFetcher
{
    private Logger $logger;
    private Database $database;
    private FeedFetcherFlags $flags;

    public function __construct(Logger $logger, Database $database, FeedFetcherFlags $flags)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->database = $database;
        $this->logger->info('FeedFetcher initialized');
    }

    public function fetch(): array
    {
        $this->fetchFeeds();
        $result = $this->fetchArticlesNotPosted();

        foreach ($result as [$articleTitle, $articleUrl, $webhookTitle, $webhookUrl]) {
            $builder = new FeedDiscordRPGenerator("$articleTitle\n$articleUrl");
            $card = $builder->process();
        }

        return [];
        //print_r($result);
    }

    private function fetchFeeds(): void
    {
        try {
            $queue = $this->prepareQueue();
            if (!empty($queue)) {
                $this->logger->info("Update needed", ['count' => count($queue)]);

                foreach ($queue as [$destId, $url]) {
                    $this->logger->info("Sending request", ['destId' => $destId, 'url' => $url]);
                    $articles = $this->parseFeeds($url);

                    if ($articles) {
                        $this->saveArticles($destId, $articles);
                    }
                }
            } else {
                $this->logger->info("No needs to update", ['queue' => $queue]);
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred during feed fetching: {$e->getMessage()}");
        }
    }

    /* これから投稿する記事のURLと投稿先とのペアが post_history テーブルの中になければ取得 */
    private function fetchArticlesNotPosted(): array
    {
        try {
            $articlesUnposted  = $this->database->prepare(
                "SELECT
                 a.title AS article_title,
                 a.url AS article_url,
                 w.title as webhook_title,
                 w.url AS webhook_url
                 FROM
                     articles a
                 INNER JOIN webhooks w ON w.source_id = 2
                 LEFT JOIN post_history ph ON ph.article_id = a.id AND ph.webhook_id = w.id
                 WHERE
                     ph.article_id IS NULL AND ph.webhook_id IS NULL"
            );
            $result = $this->fetchResults($articlesUnposted);
        } catch (\Exception $e) {
            $this->logger->error("An error occurred during feed fetching: {$e->getMessage()}");
        }

        return $result;
    }

    private function prepareQueue(): array
    {
        if ($this->flags->isForced()) {
            $this->logger->info("force-update flag is set");
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

    /** 現在時刻と最終更新時刻の差分が $maxCacheSeconds より大きい訪問先のレコードを取得 */
    private function fetchRanged($maxCacheSeconds = Config::FEED_UPDATE_LAZYNESS_SECONDS): array
    {
        $stmt = $this->database->prepare(
            "SELECT id, url FROM feeds WHERE strftime('%s', 'now') - updated_at >= :duration"
        );
        #$stmt->bindValue(':currentTime', time(), SQLITE3_INTEGER);
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

    /** 既存の記事とダウンロードした記事を重複しないようにデータベースに挿入 */
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
