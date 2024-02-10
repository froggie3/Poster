<?php

declare(strict_types=1);

namespace App\Poster;

use App\Builder\DiscordRichPresenceForecastProcessor;
use App\Config;
use App\Constants;
use App\Data\Forecast;
use App\Interface\PosterInterface;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ForecastPoster implements PosterInterface
{
    private Logger $logger;
    private Forecast $data;
    private Client $client;
    private string $url;

    public function __construct(Logger $logger, Forecast $data, string $url)
    {
        $this->logger = $logger;
        $this->data = $data;
        $this->url = $url;
        $this->client = new Client(['timeout' => Config::CONNECTION_TIMEOUT]);
    }

    public function post(): void
    {
        $hook = new DiscordRichPresenceForecastProcessor($this->data);

        $res = $this->fireWebhook($this->url, json_encode($hook->preparePayload()));

        if ($res !== false) {
            $this->logger->info("Message successfully sent");
            return;
        }
        $this->logger->error("Failed to send a message");
    }

    private function fireWebhook(string $url, string $payloadJson): string | false
    {
        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $LogHandlers = [new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()];

        $request = new Request(
            'POST',
            $url,
            ['User-Agent' => 'Mozilla/5.0', "Content-Type" => "application/json"],
            $payloadJson
        );

        $http = new Http(new Logger(Constants::MODULE_HTTP, $LogHandlers), $this->client);

        return $http->sendRequest($request);
    }
}
