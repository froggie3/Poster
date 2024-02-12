<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

class Http
{
    private Logger $logger;
    private Client $client;

    public function __construct(Logger $logger, Client $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    // recursively sends the requests until $retry runs out
    public function sendRequest(Request $request, int $retry = Config::MAX_REQUEST_RETRY): string
    {
        if ($retry == 0) {
            $this->logger->error("Maximum retries reached - terminating");
            exit(1);
        } else {

            try {
                $response = $this->client->send($request);
            } catch (GuzzleException $e) {
                $this->logger->alert("Failed to reach the API endpoint: " . $e->getMessage());
                $this->logger->alert("Retrying", ['retry left' => $retry]);

                sleep(Config::INTERVAL_REQUEST_SECONDS);
                $this->sendRequest($request, --$retry);
            }
            $body = (string)$response->getBody();

            return $body;
        }
    }
}
