<?php

declare(strict_types=1);

namespace App\Domain\Feed\Consumer;

use App\Data\Discord\DiscordPost;
use App\Domain\Feed\Cache\PostDto;
use App\Utils\DiscordPostPoster;
use Monolog\Logger;

class FeedConsumer
{
    private \PDO $db;
    private DiscordPostPoster $poster;
    private Logger $logger;
    private array $queue;
    private int $queueCount;

    public function __construct(
        Logger $logger,
        \PDO $pdo,
        DiscordPostPoster $poster,
        array $queue,
    ) {
        $this->logger = $logger;
        $this->poster = $poster;
        $this->db = $pdo;
        $this->queue = $queue;
        $this->queueCount = count($this->queue);
    }

    /**
     * Does jobs in a queue.
     */
    public function process(): void
    {
        $this->logger->info("Processing queue", ['in queue' => count($this->queue)]);

        foreach ($this->queue as $object) {
            assert($object instanceof PostDto);
            $this->queueCount--;
            $this->innerProcess($object);

            $this->addHistory($object);

            if ($this->runnable()) {
                $this->logger->debug("Waiting for the next request", ['seconds' => \App\Config::INTERVAL_REQUEST_SECONDS,]);
                sleep(\App\Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }
        }

        $this->logger->info("Finished posting");
    }

    /**
     * Whether the queue has any elements. 
     */
    protected function runnable(): bool
    {
        return !empty($this->queueCount);
    }

    protected function addHistory(PostDto $object): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO post_history_feed (posted_at, webhook_id, article_id)
            VALUES (strftime('%s', 'now'), :wid, :aid);"
        );

        $stmt->bindValue(':wid', $object->webhookId, \PDO::PARAM_INT);
        $stmt->bindValue(':aid', $object->articleId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    protected function innerProcess(object $object)
    {
        assert($object instanceof PostDto);
        $content = "{$object->articleTitle}\n{$object->articleUrl}";

        $this->poster->post(
            new DiscordPost(["content" => $content]),
            $object->webhookUrl
        );

        $this->logger->info("Message sent");
    }
}
