<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use App\Config;
use App\Constants;
use App\Data\Discord\Card;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ForecastPoster
{
    private Logger $logger;
    private Card $card;
    private Client $client;
    private string $url;

    public function __construct(Logger $logger, Card $card, string $url)
    {
        $this->logger = $logger;
        $this->card = $card;
        $this->url = $url;
        $config = ['timeout' => Config::CONNECTION_TIMEOUT_SECONDS];
        $this->client = new Client($config);
        $this->logger->debug('Client config set', $config);
    }

    public function post(): void
    {
        $res = $this->fireWebhook($this->url, json_encode($this->card));

        if ($res !== false) {
            $this->logger->info("Message successfully sent", ['response' => $res]);
            return;
        }
        $this->logger->error("Failed to send a message");
    }

    private function fireWebhook(string $url, string $payloadJson): string | false
    {
        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $logHandlers = [new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()];

        $headers = ['User-Agent' => 'Mozilla/5.0', "Content-Type" => "application/json"];
        $this->logger->debug('Client headers set', $headers);
        $request = new Request('POST', $url, $headers, $payloadJson);

        $http = new Http(new Logger(Constants::MODULE_HTTP, $logHandlers), $this->client);

        return $http->sendRequest($request);
    }
}
