<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Planner\PostingPlanner;
use App\Domain\Feed\Consumer\FeedConsumer;
use App\Domain\Feed\Updater\FeedUpdater;
use Monolog\Logger;

class Feed
{
    private \PDO $db;
    private Logger $logger;
    private Flags $flags;
    private FeedConsumer $consumer;
    private FeedUpdater $updater;
    private PostingPlanner $planner;
    private PostsArray $posts;

    public function __construct(
        Logger $logger,
        Flags $flags,
        \PDO $pdo
    ) {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->db = $pdo;
        $this->updater = new FeedUpdater(
            $this->logger,
            $this->flags,
            $this->db
        );
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
    }

    public function process(): void
    {
        if (!$this->flags->isUpdateSkipped()) {
            $this->updater->retrieveArticles();
        }

        $this->planner = new PostingPlanner($this->logger, $this->db);
        $this->posts = new PostsArray($this->planner->fetch());
        $this->consumer = new FeedConsumer($this->logger, $this->db, $this->posts);


        if (!empty($this->posts)) {
            $this->consumer->post();
        }
    }
}
