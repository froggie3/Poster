<?php

declare(strict_types=1);

namespace App\Fetcher;

use Exception;
use App\{Poster\ForecastPoster, Utils\ForecastProcess,};
use App\Interface\ForecastFetcherInterface;
use App\DataTypes\Forecast;
use App\Request\Request;
use Monolog\Logger;

use const \Config\{MAX_REQUEST_RETRY, INTERVAL_REQUEST};


class ForcastFetcher implements ForecastFetcherInterface
{
    private Logger $logger;
    private array $placeIds = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * A function that recursively sends the requests until $retry runs out.
     * on success, returns JSON-formatted string.
     * 
     * @param $placeId the location unique ID that NHK specifies.
     * @param $retry   the maximum number of times this function retries.  
     */

    private function sendRequest(string $placeId, int $retry = MAX_REQUEST_RETRY): string
    {
        $response = null;
        $r = new Request($placeId);

        $r->queries = [
            'uid' => $placeId,
            'kind' => "web",
            'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
        ];

        $this->logger->info("Sending a request to API endpoint");

        if ($retry == 0) {
            $this->logger->error("Maximum retries reached - terminating");
            exit(1);
        } else {
            $response = $r->get("https://www.nhk.or.jp/weather-data/v1/lv3/wx/?", $r->queries);
            try {
                if (!$response) {
                    throw new Exception("Failed to reach the API endpoint", 1);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            } finally {
                if ($response) {
                    $this->logger->info("The content was successfully fetched");
                    $this->logger->debug($response);
                    return $response;
                }

                $this->logger->info("Retrying ($retry)");
                sleep(INTERVAL_REQUEST);
                $this->sendRequest($placeId, --$retry);
            }
        }
    }

    public function addQueue(string $placeId)
    {
        $this->placeIds[] = $placeId;
    }

    public function fetchForecast(): array
    {
        $result = [];
        foreach ($this->placeIds as $placeId) {
            $res = $this->sendRequest($placeId);
            # $res = file_get_contents('');
            $result[] = new ForecastProcess(json_decode($res));
        }
        return $result;
    }
}
