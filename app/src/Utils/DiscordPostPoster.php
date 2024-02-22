<?php

declare(strict_types=1);

namespace App\Utils;

use App\Data\Discord\DiscordPost;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;

class DiscordPostPoster
{
    private Logger $logger;
    private DiscordPost $card;
    private Client $client;
    private string $url;

    public function __construct(Logger $logger, Client $client, DiscordPost $card, string $url)
    {
        $this->logger = $logger;
        $this->card = $card;
        $this->url = $url;

        $this->client = $client;
    }

    public function post(): void
    {
        $res = $this->fireWebhook($this->url, json_encode($this->card));
        if ($res !== false) {
            $this->logger->debug("Message successfully sent", ['response' => $res]);
            return;
        }
        $this->logger->error("Failed to send a message");
    }

    private function fireWebhook(string $url, string $payloadJson): string | false
    {
        $request = new Request('POST', $url, body: $payloadJson);
        $http = new Http($this->logger, $this->client);

        return $http->sendRequest($request);
    }
}
