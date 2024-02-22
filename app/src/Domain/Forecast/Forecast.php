<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use Monolog\Logger;
use App\Data\CommandFlags\Flags;

class Forecast
{
    private Logger $logger;
    private \PDO $pdo;
    private Flags $flags;
    private ForecastUpdater $updater;
    private ForecastPoster $poster;

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        #print_r($this->flags);
        $this->pdo = $pdo;

        $this->updater = new ForecastUpdater($this->logger, $this->flags, $this->pdo);
        $this->poster = new ForecastPoster($this->logger, $this->pdo);
    }

    public function process(): void
    {
        $this->updater->update();
        $this->poster->process();
    }
}
