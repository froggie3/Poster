<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use App\Config;
use App\Data\CommandFlags\Flags;
use Monolog\Logger;
use App\Utils\ClientFactory;
use Exception;

class ForecastUpdater
{
    private Logger $logger;
    private Flags $flags;
    private \PDO $db;

    public function __construct(Logger $logger, Flags $flags, \PDO $db)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->db = $db;
    }

    /** 
     * Get data in JSON from NHK NEWS API, and save the response in JSON as cache on success
     */
    public function update(): void
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
                } catch (Exception $e) {
                    $this->logger->warning("Failed to retrieve the weather in $placeId", ['exception' => $e]);
                }
            }
        } else {
            $this->logger->info('There is no updates found. Exiting.');
            exit;
        }
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

    public function saveCache(int $locId, string $content): bool
    {
        $query =
            "INSERT INTO
                cache_forecast (location_id, content)
            VALUES
                (:id, :res) ON CONFLICT(location_id) DO
            UPDATE
            SET
                (location_id, content) = (:id, :res);";

        $stmt = $this->db->prepare($query);

        $stmt->bindValue(':id', $locId, \PDO::PARAM_INT);
        $stmt->bindValue(':res', $content, \PDO::PARAM_STR);

        // $this->logger->info($stmt->queryString);
        $result = $stmt->execute();

        return $result;
    }
}
