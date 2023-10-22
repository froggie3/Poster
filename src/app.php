#!/usr/bin/env php
<?php

namespace App;

use App\Request\TenkiAPIRequest;
use App\Request\WebhookNHKNewsRequest;
use App\DateTypes\Weather;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger("App");
$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));

$log->info("program initiated");

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
        throw new \Exception("Error Processing Request", 1);
    }
} catch (\Exception $e) {
    $log->error($e->getMessage());
    return 1;
}

$weatherData = new Weather($placeId, $response);

/**
 * Send a POST request to Webhook API.
 */

$webhook = new WebhookNHKNewsRequest($webhookUrl, $weatherData);
$status = $webhook->send();

/**
 * Finish program.
 */

if ($status != false) {
    $log->error("failed to send a message");
    return 1;
}

$log->info("message successfully sent");

$log->info("finalizing...");
return 0;
