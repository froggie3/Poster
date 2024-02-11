<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use App\Config;
use App\Constants;
use App\Data\Query\ForecastFetcherQuery;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ForecastFetcher
{
    private Logger $logger;
    private Client $client;
    private string $placeId;

    public function __construct(Logger $logger, string $placeId)
    {
        $this->logger = $logger;
        $this->placeId = $placeId;
        $header = ['User-Agent' => 'Mozilla/5.0'];
        $config = ['timeout' => Config::CONNECTION_TIMEOUT_SECONDS, 'header' => $header,];
        $this->client = new Client($config);
        $this->logger->info('Client config set', $config);
    }


    public function fetch(): string
    {
        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $logHandlers = [new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()];

        $query = (new ForecastFetcherQuery($this->placeId))->buildQuery();
        $this->logger->debug('Query built', ['query' => $query]);
        $request = new Request('GET', "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?$query");
        $this->logger->debug('Requesting');
        $http = new Http(new Logger(Constants::MODULE_HTTP, $logHandlers), $this->client);
        $res = $http->sendRequest($request);
        $this->logger->debug('Got response', ['bytes' => strlen($res)]);

        $this->logger->info("Fetching finished");
        return $res;
    }
}
