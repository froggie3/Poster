<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Poster;

use App\Data\Discord\DiscordPost;
use App\Domain\Forecast\Poster\Processor\ForecastProcessor;
use App\Domain\Forecast\Poster\Processor\Forecast;
use App\Utils\ClientFactory;
use App\Utils\DiscordPostPoster;
use Monolog\Logger;

class ForecastPoster
{
    private Forecast $resultProcessed;
    private DiscordPost $rp;
    private Logger $logger;

    private string $placeId;
    private string $resultFetched;
    private string $webhookUrl;

    public function __construct(Logger $logger, string $placeId, string $webhookUrl, string $resultFetched)
    {
        $this->logger = $logger;
        $this->placeId = $placeId;
        $this->webhookUrl = $webhookUrl;
        $this->resultFetched = $resultFetched;
    }

    public function process(): void
    {
        $processor = new ForecastProcessor(
            $this->logger,
            $this->resultFetched // raw response
        );
        $this->resultProcessed = $processor->process();
        $this->logger->debug("Response conversion finished");

        $rpGenerator = new ForecastDiscordRPGenerator(
            $this->logger,
            $this->resultProcessed
        );
        $this->rp = $rpGenerator->process();
        $this->logger->debug("DiscordPost generation finished");

        $poster = new DiscordPostPoster(
            $this->logger,
            (new ClientFactory($this->logger, ['Content-Type' => 'application/json']))->create(),
            $this->rp,
            $this->webhookUrl
        );

        $poster->post();

        $this->logger->debug("Posting finished");
    }
}
