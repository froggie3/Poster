#!/usr/bin/env php
<?php

namespace App;

require __DIR__ . "/../vendor/autoload.php";

use Exception;
use SimpleXMLElement;

use App\FeedExtractor\RSSFeedExtractor;
use App\Request\Request;
use App\DB\Database;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$log = new Logger("App");
$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));

$prepareQueue = function (): array {
    // 3600
    $maxCacheDuration = 60 * 60 * 1;
    $db = new Database(__DIR__ . '/./sqlite.db');
    $isForced = false;
    $result = null;

    /* 現在時刻と最終更新時刻の差分が '設定期間' より大きい訪問先のレコードを取得 */
    if (!$isForced) {
        $stmt = $db->prepare('SELECT id, url FROM feeds WHERE strftime("%s") - updated_at >= :duration');
        $stmt->bindValue(':duration', $maxCacheDuration, SQLITE3_INTEGER);
        $result = $stmt->execute();
    } else {
        $result = $db->query('SELECT id, url FROM feeds');
    }

    // データベースから取ってきたデータ
    $queue = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        list($dest_id, $url) = array_values($row);

        // 成功・失敗に関する記録を残しておく一時的なオブジェクトを作る
        $queue[] = [$dest_id, $url];;
    }

    #print_r($queue);
    return $queue;
};


/* 最新のRSSフィードを取得し、失敗した場合はfalseを返す */
$getResponseText = function (string $url) use ($log): string | false {
    $req = new Request();
    $retry = 5;

    // リトライ回数が尽きるか、リクエストに成功したら終了
    while ($retry > 0) {
        $res = $req->get($url);
        //echo $res, PHP_EOL;
        if ($res) {
            return $res;
        }
        --$retry;
        $log->error("retrying: attempts left: $retry");
        sleep(2);
    }
    $log->error("failed to retrieve the feed from {url}");

    return false;
};

$retrieveFeeds = function (array $queue,) use ($log, $getResponseText) {
    // 各訪問先毎に訪問して成功したものを格納
    $requestSuccess = [];

    foreach ($queue as [$dest_id, $url]) {
        $log->info("$dest_id: sending request to $url...");
        $res = $getResponseText($url);
        if ($res) {
            $requestSuccess[] = [$dest_id, $res];
            #print_r($res);
        }
    }
    return $requestSuccess;
};

/* XMLのパースと処理 */
$parseFeeds = function ($requestSuccess) use($log): array {
    $parseSuccess = [];
    foreach ($requestSuccess as [$dest_id, $res]) {
        $entries = null;
        try {
            // Atomフィードに対応させる
            $entries = new RSSFeedExtractor(new SimpleXMLElement($res));
        } catch (Exception $e) {
            $log->error($e->getMessage());
        }
        $parseSuccess[] = [$dest_id, $entries->getEntries()];
        //print_r($articles);
    }
    return $parseSuccess;
};
