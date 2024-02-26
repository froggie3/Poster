<?php

declare(strict_types=1);

namespace App\Utils;

use App\Data\Discord\DiscordPost;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;

/**
 * A class that supports sending Webhook to Discord.
 */
class DiscordPostPoster
{
    private Logger $logger;
    private DiscordPost $card;
    private Client $client;
    private string $url;

    public function __construct(Logger $logger, Client $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * Handles message objects, and sends request to the URL.
     */
    public function post(DiscordPost $card, string $url): void
    {
        $this->url = $url;
        $this->card = $card;
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
