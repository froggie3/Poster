#!/usr/bin/php8.2
<?php

require __DIR__ . "/vendor/autoload.php";

use Minicli\App;

if (php_sapi_name() !== 'cli') {
    exit;
}

$app = new App([
    'app_path' => [
        __DIR__ . '/app/Command',
    ],
    'theme' => '\Unicorn',
    'debug' => false,
]);

$app->registerCommand('forecast', function () use ($app) {
    require_once __DIR__ . "/src/forecast.php";
});

$app->registerCommand('feed', function () use ($app) {
    require_once __DIR__ . "/src/feed.php";
});

$app->runCommand($argv);
