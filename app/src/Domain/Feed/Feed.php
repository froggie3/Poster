<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Cache\PostCache;
use App\Domain\Feed\Cache\PostsArray;
use App\Domain\Feed\Consumer\FeedConsumer;
use App\Domain\Feed\Updater\FeedUpdater;
use App\Utils\ClientFactory;
use App\Utils\DiscordPostPoster;
use GuzzleHttp\Client;
use Monolog\Logger;

class Feed
{
    private \PDO $db;
    private Logger $logger;
    private Flags $flags;
    private FeedConsumer $consumer;
    private FeedUpdater $extractor;
    private PostCache $cache;
    private PostsArray $posts;
    private Client $client;

    public function __construct(
        Logger $logger,
        Flags $flags,
        \PDO $pdo,
        Client $client,
    ) {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->db = $pdo;
        $this->extractor = new FeedUpdater(
            $this->logger,
            $this->flags,
            $this->db
        );
        $this->client = $client;
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
    }

    public function process(): void
    {
        if (!$this->flags->isUpdateSkipped()) {
            $this->extractor->process();
        }

        $this->cache = new PostCache($this->logger, $this->db);
        $queue = $this->cache->fetch();

        if (empty($queue)) {
            $this->logger->info("No forecasts to post");
            return;
        }

        $this->consumer = new FeedConsumer(
            $this->logger,
            $this->db,
            new DiscordPostPoster($this->logger, $this->client,),
            (array)$queue,
        );

        $this->consumer->process();
    }
}
