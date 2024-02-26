<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Consumer;

use App\Domain\Forecast\Consumer\ForecastDto;
use App\Domain\Forecast\Poster\Processor\ForecastProcessor;
use App\Utils\DiscordPostPoster;
use Monolog\Logger;

/**
 * A class that handles the queue.
 */
class ForecastConsumer
{
    private \PDO $db;
    private DiscordPostPoster $poster;
    private ForecastProcessor $processor;
    private Logger $logger;
    private array $queue;
    private int $queueCount;

    public function __construct(Logger $logger, \PDO $db, ForecastProcessor $processor, DiscordPostPoster $poster, array $queue,)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->processor = $processor;
        $this->poster = $poster;
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
            assert($object instanceof ForecastDto);
            $this->queueCount--;
            $this->inner_process($object);

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

    /**
     * Make a record of what was done into the database. 
     */
    protected function addHistory(ForecastDto $object): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO post_history_forecast (posted_at, webhook_id, location_id)
            VALUES (strftime('%s', 'now'), :wid, :lid);"
        );

        $stmt->bindValue(':wid', $object->webhookId, \PDO::PARAM_INT);
        $stmt->bindValue(':lid', $object->locationId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    /**
     * Procedures executed while processing every queue element 
     */
    protected function inner_process(ForecastDto $object)
    {
        assert($object instanceof ForecastDto);
        $discordPost = $this->processor->process($object->process());
        $this->logger->debug("Post generation finished");

        $this->poster->post($discordPost, $object->webhookUrl);
        $this->logger->debug("Message sent");
    }
}
