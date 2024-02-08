<?php

declare(strict_types=1);

namespace App\Fetcher;

use Exception;
use App\{Builder\DiscordRichPresenceForecastProcessor, Utils\ForecastProcess,};
use App\Interface\ForecastFetcherInterface;
use App\DataTypes\Forecast;
use App\Request\Request;
use Monolog\Logger;

use const \Config\{MAX_REQUEST_RETRY, INTERVAL_REQUEST};


class ForcastFetcher implements ForecastFetcherInterface
{
    private $logger;
    private array $placeIds = [];
    private string $webhookUrl;

    public function __construct(Logger $logger, string $webhookUrl)
    {
        $this->logger = $logger;
        $this->webhookUrl = $webhookUrl;
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

    public function fetchForecast(): void
    {
        foreach ($this->placeIds as $placeId) {
            $res = $this->sendRequest($placeId);
            # $res = file_get_contents('');

            $fp = new ForecastProcess(json_decode($res));
            $this->processWebhook($fp->process()[0], $this->webhookUrl);
        }
    }

    private function processWebhook(Forecast $data, string $url): void
    {
        $hook = new DiscordRichPresenceForecastProcessor($data);
        $res = $this->fireWebhook($url, json_encode($hook->preparePayload()));

        if ($res !== false) {
            $this->logger->info("message successfully sent to '$url'");
            return;
        }
        $this->logger->error("failed to send a message to '$url'");
    }

    private function fireWebhook(string $url, string $payloadJson): string | false
    {
        $r = new Request();
        $r->headers[] = "Content-Type: application/json";
        return $r->post($url, $payloadJson);
    }
}
