<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use Monolog\Logger;
use App\Data\Discord\Card;
use App\Utils\CardPoster;
use App\Utils\ClientFactory;
use GuzzleHttp\Client;

class Forecast
{
    private \App\Data\Forecast $resultProcessed;
    private array $queue = [];
    private Card $card;
    private Logger $logger;

    private string $placeId;
    private string $resultFetched;
    private string $webhookUrl;

    public function __construct(Logger $logger, string $placeId, string $webhookUrl)
    {
        $this->logger = $logger;
        $this->placeId = $placeId;
        $this->webhookUrl = $webhookUrl;
        $this->addQueue($this->placeId);
    }

    public function addQueue(string $placeId)
    {
        $this->logger->debug("Enqueued", ['uid' => $placeId]);
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
            $this->logger,
            (new ClientFactory(
                $this->logger,
                [
                    'User-Agent' => 'Mozilla/5.0'
                ]
            ))->create(),
            $placeId
        );
        $this->resultFetched = $fetcher->fetch();
        $this->logger->info("Fetching finished");

        $processor = new ForecastProcessor(
            $this->logger,
            $this->resultFetched
        );
        $this->resultProcessed = $processor->process();
        $this->logger->info("Response conversion finished");

        $rpGenerator = new ForecastDiscordRPGenerator(
            $this->logger,
            $this->resultProcessed
        );
        $this->card = $rpGenerator->process();
        $this->logger->info("Card generation finished");

        $poster = new CardPoster(
            $this->logger,
            (new ClientFactory(
                $this->logger,
                [
                    'User-Agent' => 'Mozilla/5.0',
                    'Content-Type' => 'application/json'
                ]
            ))->create(),
            $this->card,
            $this->webhookUrl
        );
        $poster->post();
        $this->logger->info("Posting finished");
    }
}
