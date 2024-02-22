<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Consumer;

use App\Config;
use App\Domain\Forecast\Poster\ForecastPoster;
use App\Domain\Forecast\Cache\ForecastQueue;
use Monolog\Logger;

class ForecastConsumer
{
    private array $queue = [];
    private Logger $logger;
    private \PDO $db;

    public function __construct(Logger $logger, \PDO $db, ForecastQueue $queue)
    {
        $this->logger = $logger;
        $this->queue = (array)$queue;
        $this->db = $db;
    }

    public function process(): void
    {
        if (empty($this->queue)) {
            $this->logger->info("No forecasts to post");
            return;
        }

        while (true) {
            if (!empty($this->queue)) {
                sleep(Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }

            $p = array_shift($this->queue);
            $this->logger->debug("Processing", ['queue count' => count($this->queue), 'uid' => $p->placeId]);

            $f = new ForecastPoster($this->logger, $p->placeId, $p->webhookUrl, $p->content);
            $f->process();

            $this->addHistory($p);
        }

        $this->logger->info("Finished posting");
    }

    public function addHistory(ForecastDto $p): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO post_history_forecast (posted_at, webhook_id, location_id)
            VALUES (strftime('%s', 'now'), :wid, :lid)
            ON CONFLICT (webhook_id, location_id) DO NOTHING"
        );

        $stmt->bindValue(':wid', $p->webhookId, \PDO::PARAM_INT);
        $stmt->bindValue(':lid', $p->locationId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }
}
