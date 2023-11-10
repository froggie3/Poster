#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace App;

use App\Request\TenkiAPIRequest;
use App\Request\WebhookNHKNewsRequest;
use App\DataTypes\Weather;
use App\Utils\Telop;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger("App");
$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));

$log->info("program initialized");

$dotenvDirectory = __DIR__ . "/../.env";
$log->info("attempting to load dotenv from $dotenvDirectory");

try {
    if (!$env = parse_ini_file($dotenvDirectory)) {
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

$webhookUrl = $env["WEBHOOK_URL"];
$placeId = $env["PLACE_ID"];

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

$weatherData = new Weather();
$weatherData->locationUid = $placeId;
$weatherData->locationName = (
    fn ($prefecture, $districtName) => $prefecture . $districtName)(
    $response['lv2_info']['name'],
    $response['name']
);
$weatherData->forecastDate = $response['created_date'];

// forecast for three days
$forecastThreeDays = $response['trf']['forecast'];

// forecast only for today
$weatherData->maxTemp = $forecastThreeDays[0]['max_temp'];
$weatherData->maxTempDiff = $forecastThreeDays[0]['max_temp_diff'];
$weatherData->minTemp = $forecastThreeDays[0]['min_temp'];
$weatherData->minTempDiff = $forecastThreeDays[0]['min_temp_diff'];
$weatherData->rainyDay = $forecastThreeDays[0]['rainy_day'];
$weatherData->telop = $forecastThreeDays[0]['telop'];
[
    $weatherData->weather,
    $weatherData->weatherEmoji,
    $weatherData->telopFile
] = Telop::TelopData[$weatherData->telop];


/**
 * Send a POST request to Webhook API.
 */

$webhook = new WebhookNHKNewsRequest($webhookUrl, $weatherData);
$status = $webhook->send();

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
