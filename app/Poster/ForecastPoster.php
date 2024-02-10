<?php

declare(strict_types=1);

namespace App\Poster;

use App\Builder\DiscordRichPresenceForecastProcessor;
use App\Interface\PosterInterface;
use App\DataTypes\Forecast;
use App\Request\Request;
use Monolog\Logger;


class ForecastPoster implements PosterInterface
{
    private Logger $logger;
    private Forecast $data;
    private string $url;

    public function __construct(Logger $logger, Forecast $data, string $url)
    {
        $this->logger = $logger;
        $this->data = $data;
        $this->url = $url;
    }

    public function post(): void
    {
        $hook = new DiscordRichPresenceForecastProcessor($this->data);
        $res = $this->fireWebhook($this->url, json_encode($hook->preparePayload()));

        if ($res !== false) {
            $this->logger->info("message successfully sent to '$this->url'");
            return;
        }
        $this->logger->error("failed to send a message to '$this->url'");
    }

    private function fireWebhook(string $url, string $payloadJson): string | false
    {
        $r = new Request();
        $r->headers[] = "Content-Type: application/json";
        return $r->post($url, $payloadJson);
    }
}
