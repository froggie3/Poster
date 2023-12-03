#!/usr/bin/env php
<?php

namespace App;

use App\Request\TenkiAPIRequest;
use App\Request\WebhookNHKNewsRequest;
use App\DataTypes\Weather;
use App\Utils\Algorithm;
use App\Utils\Telop;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
    echo $e->getMessage(), "\n";
    return 1;
}

echo "dotenv was successfully loaded: $dotenvDirectory\n";

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

function composeDataPackage(array $jsonResponse, string $placeId, int $index = 0): Weather
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
    [$weatherData->weather, $weatherData->weatherEmoji, $weatherData->telopFile] =
        Telop::TelopData[$weatherData->telop];

    return $weatherData;
}

function prepareWeatherData(array $response, string $id): array
{
    $weatherDataComplex = [];
    for ($i = 0; $i < 3; $i++) {
        $weatherDataComplex[] = (composeDataPackage($response, $id, $i));
    }
    return $weatherDataComplex;
}

// forecast only for today (provisional) 
$weatherData = (prepareWeatherData($response, $placeId))[0];

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