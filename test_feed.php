#!/usr/bin/env php
<?php

namespace App;

require __DIR__ . "/./vendor/autoload.php";
require __DIR__ . "/./src/feed.php";

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$log = new Logger("App");
$log->pushHandler(new StreamHandler(__DIR__ . "/./logs/app.log", Level::Debug));

$queue = $prepareQueue();
$parsed = $retrieveFeeds($queue);
$saveDatabase($parsed);
# $parsed = $parseFeeds($articleTexts);

#print_r($parsed);
