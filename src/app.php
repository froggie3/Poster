#!/usr/bin/env php
<?php

namespace App;

use App\Request\TenkiAPIRequest;
use App\Request\WebhookNHKNewsRequest;
use App\DateTypes\Weather;

try {
    $dotenvDirectory = dirname('..') . "/.env";
    if (!$env = parse_ini_file($dotenvDirectory)) {
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

$webhookUrl = $env["WEBHOOK_URL"];
$placeId = $env["PLACE_ID"];

/**
 * Send a request to API endpoint.
 */

$fetch = new TenkiAPIRequest($placeId);

try {
    if (!$response = $fetch->fetch()) {
        throw new \Exception("Error Processing Request", 1);
    }
} catch (\Exception $e) {
    echo $e->getMessage();
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
    echo "failed to send a message\n";
    return 1;
}

echo "message successfully sent\n";
return 0;
