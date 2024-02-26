<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use Monolog\Logger;
use App\Data\CommandFlags\Flags;
use App\Domain\Forecast\Cache\Fetcher\ForecastFetcher;
use App\Domain\Forecast\Cache\ForecastCache;
use App\Domain\Forecast\Consumer\ForecastConsumer;
use App\Domain\Forecast\Poster\Processor\ForecastProcessor;
use App\Utils\DiscordPostPoster;
use GuzzleHttp\Client;


/**
 * A class that handles the whole process of retrieving and posting forecast.
 */
class Forecaster
{
    private Logger $logger;
    private \PDO $pdo;
    private Flags $flags;
    private ForecastCache $updater;
    private ForecastConsumer $poster;
    private Client $client;

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo, Client $client)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->client = $client;
        $this->pdo = $pdo;
    }

    public function process(): void
    {
        $this->updater = new ForecastCache(
            $this->logger,
            $this->flags,
            $this->pdo,
            $this->client,
            new ForecastFetcher($this->logger, $this->client)
        );

        $this->updater->updateCache();
        $queue = $this->updater->retrieveForecastFromCache();

        if (!$queue->needsUpdate()) {
            $this->logger->info("No forecasts to post");
            return;
        }

        $this->poster = new ForecastConsumer(
            $this->logger,
            $this->pdo,
            new ForecastProcessor($this->logger),
            new DiscordPostPoster($this->logger, $this->client),
            (array)$queue,
        );

        $this->poster->process();
    }
}
