<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Cache\Fetcher;

use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;


class ForecastFetcher
{
    private Logger $logger;
    private Client $client;
    private string $placeId;

    public function __construct(Logger $logger, Client $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    /** 
     * Get and returns the data in JSON from NHK NEWS API.
     */
    public function fetch(string $placeId): string
    {
        $this->placeId = $placeId;
        $query = (new ForecastFetcherQuery($this->placeId))->buildQuery();
        $this->logger->debug('Query built', ['query' => $query]);
        $request = new Request('GET', "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?$query");

        $this->logger->debug('Requesting');
        $http = new Http($this->logger, $this->client);
        $res = $http->sendRequest($request);
        $this->logger->debug('Got response', ['bytes' => strlen($res)]);

        return $res;
    }
}
