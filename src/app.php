#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace App;

use App\Request\TenkiAPIRequest;
use App\Request\WebhookNHKNewsRequest;
use App\DataTypes\Weather;
use App\Utils\Algorithm;
use App\DataTypes\Telop;
use App\DataTypes\TelopImageUsed;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$telops = [
    100 => new Telop('晴れ', ':sunny:', 'tlp100.png', -1, new TelopImageUsed(true, true),),
    101 => new Telop('晴れ時々くもり', ':partly_sunny:', 'tlp101.png', -1, new TelopImageUsed(true, true),),
    102 => new Telop('晴れ一時雨', ':white_sun_rain_cloud:', 'tlp102.png', -1, new TelopImageUsed(true, true),),
    103 => new Telop('晴れ時々雨', ':white_sun_rain_cloud:', 'tlp103.png', -1, new TelopImageUsed(true, true),),
    104 => new Telop('晴れ一時雪', ':white_sun_rain_cloud:', 'tlp105.png', -1, new TelopImageUsed(true, false)),
    105 => new Telop('晴れ一時雪', ':white_sun_rain_cloud:', 'tlp105.png', 104, new TelopImageUsed(false, true)),
    110 => new Telop('晴れのちくもり', ':white_sun_cloud:', 'tlp110.png', -1, new TelopImageUsed(false, true)),
    111 => new Telop('晴れのちくもり', ':white_sun_cloud:', 'tlp110.png', 110, new TelopImageUsed(true, false)),
    113 => new Telop('晴れのち雨', ':white_sun_rain_cloud:', 'tlp113.png', -1, new TelopImageUsed(false, true),),
    114 => new Telop('晴れのち雨', ':white_sun_rain_cloud:', 'tlp113.png', 113, new TelopImageUsed(true, false),),
    115 => new Telop('晴れのち雪', ':white_sun_rain_cloud:', 'tlp115.png', -1, new TelopImageUsed(true, true),),
    200 => new Telop('くもり', ':cloud:', 'tlp200.png', -1, new TelopImageUsed(true, true),),
    201 => new Telop('くもり時々晴れ', ':partly_sunny:', 'tlp201.png', -1, new TelopImageUsed(true, true),),
    202 => new Telop('くもり一時雨', ':cloud_rain:', 'tlp202.png', -1, new TelopImageUsed(true, true),),
    203 => new Telop('くもり時々雨', ':cloud_rain:', 'tlp203.png', -1, new TelopImageUsed(true, true),),
    204 => new Telop('くもり一時雪', ':cloud_rain:', 'tlp205.png', -1, new TelopImageUsed(true, false),),
    205 => new Telop('くもり一時雪', ':cloud_rain:', 'tlp205.png', 204, new TelopImageUsed(false, true),),
    210 => new Telop('くもりのち晴れ', ':white_sun_cloud:', 'tlp210.png', 211, new TelopImageUsed(false, true),),
    211 => new Telop('くもりのち晴れ', ':white_sun_cloud:', 'tlp210.png', -1, new TelopImageUsed(true, false),),
    213 => new Telop('くもりのち雨', ':cloud_rain:', 'tlp213.png', -1, new TelopImageUsed(false, true),),
    214 => new Telop('くもりのち雨', ':cloud_rain:', 'tlp213.png', 213, new TelopImageUsed(true, false),),
    215 => new Telop('くもりのち雪', ':cloud_rain:', 'tlp215.png', -1, new TelopImageUsed(false, true),),
    217 => new Telop('くもりのち雪', ':cloud_rain:', 'tlp215.png', 215, new TelopImageUsed(true, false),),
    300 => new Telop('雨', ':cloud_rain:', 'tlp300.png', -1, new TelopImageUsed(true, true),),
    301 => new Telop('雨時々晴れ', ':white_sun_rain_cloud:', 'tlp301.png', -1, new TelopImageUsed(true, true),),
    302 => new Telop('雨一時くもり', ':white_sun_small_cloud:', 'tlp302.png', -1, new TelopImageUsed(true, true),),
    303 => new Telop('雨時々雪', ':cloud_snow:', 'tlp303.png', -1, new TelopImageUsed(true, true),),
    308 => new Telop('暴風雨', ':cloud_rain:', 'tlp308.png', -1, new TelopImageUsed(true, true),),
    311 => new Telop('雨のち晴れ', ':white_sun_rain_cloud:', 'tlp311.png', -1, new TelopImageUsed(true, true),),
    313 => new Telop('雨のちくもり', ':cloud_rain:', 'tlp313.png', -1, new TelopImageUsed(true, true),),
    315 => new Telop('雨のち雪', ':cloud_snow:', 'tlp315.png', -1, new TelopImageUsed(true, true),),
    400 => new Telop('雪', ':snowflake:', 'tlp400.png', -1, new TelopImageUsed(true, true),),
    401 => new Telop('雪時々晴れ', ':cloud_snow:', 'tlp401.png', -1, new TelopImageUsed(true, true),),
    402 => new Telop('雪時々やむ', ':cloud_snow:', 'tlp402.png', -1, new TelopImageUsed(true, true),),
    403 => new Telop('雪時々雨', ':cloud_snow:', 'tlp403.png', -1, new TelopImageUsed(true, true),),
    407 => new Telop('暴風雪', ':cloud_snow:', 'tlp407.png', -1, new TelopImageUsed(true, true),),
    409 => new Telop('雪時々雨', ':cloud_snow:', 'tlp409.png', -1, new TelopImageUsed(true, true),),
    411 => new Telop('雪のち晴れ', ':white_sun_rain_cloud:', 'tlp411.png', -1, new TelopImageUsed(true, true),),
    413 => new Telop('雪のちくもり', ':cloud_snow:', 'tlp413.png', -1, new TelopImageUsed(true, true),),
    414 => new Telop('雪のち雨', ':cloud_snow:', 'tlp414.png', -1, new TelopImageUsed(true, true),),
];


$log = new Logger("App");
$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));

$log->info("program initialized");

function openEnvVariables($path): array | false
{
    return parse_ini_file($path);
}

$dotenvDirectory = __DIR__ . "/../.env";
$log->info("attempting to load dotenv from $dotenvDirectory");

try {
    if (!$env = openEnvVariables($dotenvDirectory)) {
        throw new \Exception("bad .env file", 1);
    }
} catch (\Exception $e) {
    $log->error($e->getMessage());
    return 1;
}

$log->info("dotenv was successfully loaded");

/**
 * Get environmental variables from .env file.
 */

function parseEnvVariables(string $parameter): array
{
    $configArray = [];
    if (!$parameter) return $configArray;
    foreach (explode(",", $parameter) as $v) {
        $configArray[] = rtrim($v);
    }
    return $configArray;
}

$webhookUrl = parseEnvVariables($env["WEBHOOK_URL"])[0];
$placeId = parseEnvVariables($env["PLACE_ID"])[0];

$log->info("webhook destination: $webhookUrl");
$log->info("place uid: $placeId");

/**
 * Send a request to API endpoint.
 */

$fetch = new TenkiAPIRequest($placeId);

$log->info("sending a request to API endpoint");

try {
    if (!$response = $fetch->fetch()) {
        throw new \Exception("failed to reach the API endpoint", 1);
    }
} catch (\Exception $e) {
    $log->error($e->getMessage());
    return 1;
}

/**
 * Packing the retrieved data into a dedicated data class.
 */

function composeDataPackage(array $jsonResponse, string $placeId, array $telops, int $index = 0): Weather
{
    $index = Algorithm::clamp($index, 0, 2);

    [$prefecture, $district, $forecastDate, $forecastThreeDays] = [
        $jsonResponse['lv2_info']['name'],
        $jsonResponse['name'],
        $jsonResponse['created_date'],
        $jsonResponse['trf']['forecast']
    ];

    $weatherData = new Weather();
    $weatherData->locationUid = $placeId;
    $weatherData->locationName = $prefecture . $district;
    $weatherData->forecastDate = $forecastDate;

    $weatherData->maxTemp      = $forecastThreeDays[$index]['max_temp'];
    $weatherData->maxTempDiff  = $forecastThreeDays[$index]['max_temp_diff'];
    $weatherData->minTemp      = $forecastThreeDays[$index]['min_temp'];
    $weatherData->minTempDiff  = $forecastThreeDays[$index]['min_temp_diff'];
    $weatherData->rainyDay     = $forecastThreeDays[$index]['rainy_day'];
    $weatherData->telop        = $forecastThreeDays[$index]['telop'];

    $obj = $telops[$weatherData->telop];
    $weatherData->weather = $obj->distinct_name;
    $weatherData->weatherEmoji = $obj->emoji_name;
    $weatherData->telopFile = $obj->telop_filename;

    return $weatherData;
}

function prepareWeatherData(array $response, string $id, array $telops): array
{
    $weatherDataComplex = [];
    for ($i = 0; $i < 3; $i++) {
        $weatherDataComplex[] = (composeDataPackage($response, $id, $telops, $i));
    }
    return $weatherDataComplex;
}

// forecast only for today (provisional) 
$weatherData = (prepareWeatherData($response, $placeId, $telops))[0];

// prepare the queue based on Webhook URLs
$sendQueue = [
    [$weatherData, $webhookUrl]
];

/**
 * Send a POST request to Webhook API
 */

while ($sendQueue) {
    [$data, $dest,] = array_shift($sendQueue);
    $webhook = new WebhookNHKNewsRequest($dest, $data);
    $status = $webhook->send();
}

/**
 * Finish program.
 */

if (!$status) {
    $log->error("failed to send a message");
    return 1;
}
$log->info("message successfully sent");
$log->info("finalizing...");
return 0;
