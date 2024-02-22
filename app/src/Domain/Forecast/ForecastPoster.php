<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use Monolog\Logger;
use App\Config;

class ForecastPoster
{
    private array $queue = [];
    private Logger $logger;
    private \PDO $db;

    public function __construct(Logger $logger, \PDO $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Retrieve data from cache
     */
    public function retrieve(): void
    {
        $query =
            "SELECT
                webhooks.id as webhookId,
                locations.id as locationId,
                locations.place_id as placeId,
                webhooks.url as webhookUrl,
                cache_forecast.content as content
            FROM
                webhook_map_forecast
                INNER JOIN locations ON webhook_map_forecast.location_id = locations.id
                INNER JOIN webhooks ON webhooks.id = webhook_map_forecast.webhook_id
                INNER JOIN cache_forecast ON cache_forecast.location_id = locations.id;";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $this->queue[] = new ForecastDto($row);
        }
    }

    public function process(): void
    {
        $this->retrieve();

        if (empty($this->queue)) {
            $this->logger->info("No forecasts to post");
            return;
        }

        $this->processQueue();
    }

    private function processQueue(): void
    {
        while (true) {
            if (empty($this->queue)) {
                sleep(Config::INTERVAL_REQUEST_SECONDS);
                break;
            }

            $p = array_shift($this->queue);
            $this->logger->debug("Processing", ['queue count' => count($this->queue), 'uid' => $p->placeId]);

            $f = new PostForecast($this->logger, $p->placeId, $p->webhookUrl, $p->content);
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
