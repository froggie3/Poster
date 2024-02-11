<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use \App\Config;
use \App\Constants;
use \Monolog\Handler\ErrorLogHandler;
use \Monolog\Handler\StreamHandler;
use \Monolog\Logger;

class Forecast
{
    private Logger $logger;
    private string $placeId;
    private string $webhookUrl;
    private array $queue = [];
    private string $loggingPath;
    private array $logHandlers;

    function __construct(Logger $logger, string $placeId, string $webhookUrl)
    {
        $this->logger = $logger;
        $this->placeId = $placeId;
        $this->webhookUrl = $webhookUrl;
        $this->loggingPath = __DIR__ . '/../../../logs/app.log';
        $this->logHandlers = [
            new StreamHandler($this->loggingPath, Config::MONOLOG_LOG_LEVEL),
            new ErrorLogHandler()
        ];
        $this->addQueue($this->placeId);
    }

    public function addQueue(string $placeId)
    {
        $this->logger->info("Enqueued", ['uid' => $placeId]);
        $this->queue[] = $placeId;
    }

    public function process(): void
    {
        $this->logger->debug("process() called", ['queue count' => count($this->queue)]);
        foreach ($this->queue as $placeId) {
            $this->logger->info("Processing", ['uid' => $placeId]);
            $this->processInside($placeId);
        }
    }

    private function processInside(string $placeId): void
    {
        $fetcher = new ForecastFetcher(
            new Logger(Constants::MODULE_FORECAST_FETCHER, $this->logHandlers),
            $placeId
        );
        $resultFetched = $fetcher->fetch();
        $processor = new ForecastProcessor($resultFetched);
        $resultProcessed = $processor->process()[0];
        $rpGenerator = new ForecastDiscordRPGenerator($resultProcessed);
        $card = $rpGenerator->process();
        $poster = new ForecastPoster(
            new Logger(Constants::MODULE_FORECAST_POSTER, $this->logHandlers),
            $card,
            $this->webhookUrl
        );
        $poster->post();
    }
}
