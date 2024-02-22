<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use Monolog\Logger;
use App\Data\CommandFlags\Flags;
use App\Domain\Forecast\Cache\ForecastCache;
use App\Domain\Forecast\Consumer\ForecastConsumer;

class Forecaster
{
    private Logger $logger;
    private \PDO $pdo;
    private Flags $flags;
    private ForecastCache $updater;
    private ForecastConsumer $poster;

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        #print_r($this->flags);
        $this->pdo = $pdo;

        $this->updater = new ForecastCache($this->logger, $this->flags, $this->pdo);
    }

    public function process(): void
    {
        $this->updater->updateCache();
        $queue = $this->updater->retrieveForecastFromCache();

        $this->poster = new ForecastConsumer($this->logger, $this->pdo, $queue);
        $this->poster->process();
    }
}
