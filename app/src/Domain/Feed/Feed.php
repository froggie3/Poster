<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\CommandFlags\FeedFetcherFlags;
use App\DB\Database;
use App\Utils\CardPoster;
use App\Utils\ClientFactory;
use FeedIo\Adapter\Http\Client;
use Monolog\Logger;


class Feed
{
    private Database $db;
    private FeedFetcherFlags $flags;
    private Logger $logger;
    private array $articles = [];  // サイトの識別番号をキーにもつ記事リスト
    private array $postQueue = [];  // 投稿キュー

    public function __construct(Logger $logger, FeedFetcherFlags $flags)
    {
        $this->flags = $flags;
        $this->logger = $logger;
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
        $this->logger = $logger;
        //$this->queue = $queue;

        $this->setDatabaseReady();

        $this->logger->info('FeedFetcher initialized');
    }


    public function process(): void
    {
        $qf = new QueueFeed($this->logger, $this->db, $this->flags);
        $qf->fetch();

        if (!$qf->needsUpdate()) {
            $this->logger->info('No needs to update');
            return;
        }

        // visit feed URLs for each website 
        $results = $this->fetchFeeds($qf->getProviders());
        $this->articles = $this->filter($results);

        // save articles into sqlite3 database
        $saver = new FeedSaver($this->logger, $this->db, $this->articles);
        $saver->save();

        // post webhooks from sqlite3 database
        $postq = new QueuePost($this->logger, $this->db);
        $postq->process();
        $this->postQueue = $postq->fetch();
        //print_r($result);

        $this->post();
    }

    private function fetchFeeds(array $providers): array
    {
        $results = [];
        foreach ($providers as [$destId, $url]) {
            $this->logger->info('Processing', ['destination' => $destId]);
            $results[$destId] = $this->request($url);
        }
        return $results;
    }

    /** filters unwanted information */
    private function filter(array $results): array 
    {
        $articles = [];
        foreach ($results as $result) {
            $filter = new FeedFilter($result);
            $filter->process();
            $this->articles[$destId] = $filter->get();
        }
        return $articles;
    }

    private function request(string $url): \FeedIo\Reader\Result
    {
        $feedIo = new \FeedIo\FeedIo($this->createClient(), $this->logger);
        $fetcher = new FeedFetcher($this->logger, $feedIo);

        return $fetcher->fetch($url);
    }

    private function createClient(): Client
    {
        $cf = new ClientFactory($this->logger, [] /*['User-Agent' => 'Mozilla/5.0']*/);
        $client = $cf->create();
        return new \App\FeedIo\Adapter\Http\Client($client);
    }


    private function post(): void
    {
        $this->logger->debug("Processing queue", [
            'in queue' => count($this->postQueue)
        ]);

        $cf = new ClientFactory($this->logger, [
            "Content-Type" => "application/json"
        ]);
        $client = $cf->create();

        while (true) {
            [$articleId, $articleTitle, $articleUrl, $webhookId, $webhookTitle, $webhookUrl] = array_pop($this->postQueue);
            $content = "$articleTitle\n$articleUrl";
            $builder = new FeedDiscordRPGenerator($content);
            $card = $builder->process();
            $poster = new CardPoster($this->logger, $client, $card, $webhookUrl);

            $poster->post();
            $this->addHistory($webhookId, $articleId);

            $this->logger->debug("Success", ['in queue' => count($this->postQueue)]);

            if (!empty($this->postQueue)) {
                $this->logger->debug(
                    "Waiting for the next request",
                    ['seconds' => \App\Config::INTERVAL_REQUEST_SECONDS,]
                );
                sleep(\App\Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }
        }
    }

    private function addHistory(int $webhookId, int $articleId)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO post_history (post_date, webhook_id, article_id, location_id, source_id)
            VALUES (strftime('%s', 'now'), :wid, :aid, NULL, 2)"
        );

        $stmt->bindValue(':wid', $webhookId, SQLITE3_INTEGER);
        $stmt->bindValue(':aid', $articleId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        return $result;
    }

    private function setDatabaseReady(): void
    {
        $databasePath = __DIR__ . '/../../../../sqlite.db';
        if (!empty($this->flags->getDatabasePath()))
            $databasePath = $this->flags->getDatabasePath();

        try {
            if (!file_exists($databasePath))
                throw new \SQLite3Exception('Database not found');
        } catch (\SQLite3Exception $e) {
            $this->logger->error($e->getMessage());
            exit;
        }

        $this->db = new Database($databasePath);
    }
}
