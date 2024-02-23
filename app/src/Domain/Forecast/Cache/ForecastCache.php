<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Cache;

use App\Config;
use App\Data\CommandFlags\Flags;
use App\Domain\Forecast\Cache\Fetcher\ForecastFetcher;
use App\Domain\Forecast\Consumer\ForecastDto;
use App\Utils\ClientFactory;
use Monolog\Logger;

class ForecastCache
{
    private Logger $logger;
    private Flags $flags;
    private ForecastArray $queue;
    private \PDO $db;

    public function __construct(Logger $logger, Flags $flags, \PDO $db)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->queue = new ForecastArray();
        $this->db = $db;
    }

    /** 
     * Get data in JSON from NHK NEWS API, and save the response in JSON as cache on success
     */
    public function updateCache(): void
    {
        if ($this->flags->isForced()) {
            $this->logger->alert("'--force-update' is set");
        }

        $stmt = $this->db->prepare(
            $this->flags->isForced()
                ? "SELECT id, place_id FROM locations"
                : "SELECT id, place_id FROM locations
                   WHERE strftime('%s', 'now') - updated_at >= :cache"
        );

        if (!$this->flags->isForced()) {
            $stmt->bindValue(':cache', Config::FEED_CACHE_LIFETIME, \PDO::PARAM_INT);
        }
        $stmt->execute();

        $queue = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //print_r($queue);

        if (!empty($queue)) {
            $this->logger->info("Update needed");

            foreach ($queue as ["id" => $locId, "place_id" => $placeId]) {
                $fetcher = new ForecastFetcher($this->logger, (new ClientFactory($this->logger,))->create(), $placeId);

                try {
                    $res = $fetcher->fetch();
                    $this->saveCache($locId, $res);

                    $updatedResult = $this->updateLocations($locId);
                    if ($updatedResult) {
                        $this->logger->debug('Updated last updated time');
                    }
                    $this->logger->debug("Fetched forecast", ["placeId" => $placeId]);
                } catch (\Exception $e) {
                    $this->logger->warning("Failed to retrieve the weather in $placeId", ['exception' => $e]);
                }
            }
        } else {
            $this->logger->info('There is no updates found. Exiting.');
            exit;
        }
    }

    /**
     * Retrieve data from cache
     */
    public function retrieveForecastFromCache(): ForecastArray
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
                INNER JOIN cache_forecast ON cache_forecast.location_id = locations.id
            WHERE
                webhook_map_forecast.enabled = 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $this->queue[] = new ForecastDto($row);
        }
        return $this->queue;
    }

    /** Updates the table 'locations' when updated */
    private function updateLocations(int $locId)
    {
        $query =
            "UPDATE
                locations
            SET
                updated_at = strftime('%s', 'now')
            WHERE id = :locId";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':locId', $locId, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function saveCache(int $locId, string $content): bool
    {
        $query =
            "INSERT OR REPLACE INTO cache_forecast (location_id, content)
            VALUES (:id, :res);";

        $stmt = $this->db->prepare($query);

        $stmt->bindValue(':id', $locId, \PDO::PARAM_INT);
        $stmt->bindValue(':res', $content, \PDO::PARAM_STR);

        // $this->logger->info($stmt->queryString);
        $result = $stmt->execute();

        return $result;
    }
}
