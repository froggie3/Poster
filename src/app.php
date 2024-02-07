#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace App;

define("MAX_REQUEST_RETRY", 5);
define("INTERVAL_REQUEST",  2);

use App\{
    Request\TenkiAPIRequest,
    Builder\ForecastRichPresenceBuilder,
    Parser\EnvVariablesParser,
    Utils\ForecastProcess,
};
use App\Request\Request;
use Monolog\{
    Level,
    Logger,
    Handler\StreamHandler,
    Handler\ErrorLogHandler,
};

use Exception;

function openEnvVariables($path): array | false
{
    return parse_ini_file($path);
}

$log = new Logger("App");
//$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));
$log->pushHandler(new ErrorLogHandler());

$dotenvDirectory = __DIR__ . "/../.env";
$log->info("attempting to load dotenv from '$dotenvDirectory'");

try {
    if (!$env = openEnvVariables($dotenvDirectory)) {
        throw new Exception("bad .env file", 1);
    }
    $log->info("dotenv was successfully loaded");
} catch (Exception $e) {
    $log->error($e->getMessage());
    return 1;
}


/**
 * Get environmental variables from .env file.
 */

$log->debug(json_encode($env));
$webhookUrl = EnvVariablesParser::parse($env["WEBHOOK_URL"])[0];
$placeId    = EnvVariablesParser::parse($env["PLACE_ID"])[0];

$log->info("location UID to get: '$placeId'");


/**
 * a function that recursively sends the requests until $retry runs out.
 * on success, returns JSON-formatted string.
 * 
 * @param $placeId the location unique ID that NHK specifies.
 * @param $retry   the maximum number of times this function retries.  
 */

$sendRequest = function ($placeId, $retry = MAX_REQUEST_RETRY) use ($log, &$sendRequest): string {
    $response = null;
    $r = new Request($placeId);

    $r->queries = [
        'uid' => $placeId,
        'kind' => "web",
        'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
    ];

    $log->info("sending a request to API endpoint");

    if ($retry == 0) {
        $log->error("maximum retries reached - terminating");
        exit(1);
    } else {
        $response = $r->get("https://www.nhk.or.jp/weather-data/v1/lv3/wx/?", $r->queries);
        try {
            if (!$response) {
                throw new Exception("failed to reach the API endpoint", 1);
            }
        } catch (Exception $e) {
            $log->error($e->getMessage());
        } finally {
            if ($response) {
                $log->info("the content was successfully fetched");
                $log->debug($response);
                return $response;
            }

            $log->info("retrying ($retry)");
            sleep(INTERVAL_REQUEST);
            $sendRequest($placeId, --$retry);
        }
    }
    unset($r);
};

$res = $sendRequest($placeId);
#$res = file_get_contents('/home/iigau/dev/forecast/src/tests/weather_data.json');


$data = json_decode($res);
$log->debug("json was successfully parsed");

/**
 * Packing the parsed data into a dedicated data class.
 */

// forecast only for today (provisional)
list($forecasts, $_, $_) = (new ForecastProcess(response: $data))
    ->processThreeDays();
// prepare the queue based on Webhook URLs
$sendQueue[] = [$forecasts, $webhookUrl];

/**
 * Send a POST request to Webhook API
 */

while ($sendQueue) {
    [$data, $dest,] = array_shift($sendQueue);
    $wf = new ForecastRichPresenceBuilder($data);
    $payload = $wf->preparePayload();
    $payloadJson = json_encode($payload, JSON_PRETTY_PRINT);
    print_r($payloadJson);
    #$log->debug($payloadJson);

    $r = new Request();
    $r->headers[] = "Content-Type: application/json";

    if ($r->post($dest, $payloadJson) !== false) {
        $log->info("message successfully sent to '$dest'");
    } else {
        $log->error("failed to send a message to '$dest'");
    }
}

return 0;
