#!/usr/bin/env php
<?php

namespace App;

require __DIR__ . "/../vendor/autoload.php";

use App\DB\Database;
use App\DataTypes\Article;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Level;
use Monolog\Logger;



$log = new Logger("App");
#$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Level::Debug));
$log->pushHandler(new ErrorLogHandler());


$prepareQueue = function (): array {
    // 3600
    $maxCacheDuration = 60 * 60 * 1;
    $db = new Database(__DIR__ . '/../sqlite.db');
    $isForced = true;
    $result = null;

    // 現在時刻と最終更新時刻の差分が '設定期間' より大きい訪問先のレコードを取得
    if (!$isForced) {
        $stmt = $db->prepare(
            <<<EOM
            SELECT id, url
            FROM
                feeds
            WHERE
                strftime("%s") - updated_at >= :duration
        EOM
        );
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


/* フィードのパースと処理 */
$parseFeeds = function ($url) use ($log): array {
    $client = new \FeedIo\Adapter\Http\Client(new \Symfony\Component\HttpClient\HttplugClient());
    $feedIo = new \FeedIo\FeedIo($client);
    $result = $feedIo->read($url);
    $parseSuccess = [];

    foreach ($result->getFeed() as $item) {
        $parseSuccess[] = new Article(
            title: $item->getTitle(),
            link: $item->getLink(),
            updatedAt: $item->getLastModified()
        );
    }
    return $parseSuccess;
};

$retrieveFeeds = function (array $queue,) use ($log, $parseFeeds) {
    // 各訪問先毎に訪問
    $requestSuccess = [];

    foreach ($queue as [$dest_id, $url]) {
        $log->info("$dest_id: sending request to $url...");
        $articles = $parseFeeds($url);

        //成功したら格納
        if ($articles) {
            $requestSuccess[] = [$dest_id, $articles];
            #print_r($articles);
        }
    }

    return $requestSuccess;
};

$saveDatabase = function ($parsedArticles) use ($log) {
    $db = new Database(__DIR__ . '/../sqlite.db');

    //$log->info("successfully written to datebase");
    $log->info("saving articles into the datebase");
    foreach ($parsedArticles as [$feed_id, $articles]) {
        foreach ($articles as $article) {
            // print_r([$feed_id, $article]);

            $stmt = $db->prepare(
                <<<EOM
                INSERT OR IGNORE INTO 
                    articles (title, url, updated_at, created_at, feed_id)
                VALUES
                    (
                        :title,
                        :url, 
                        :updated_at, 
                        strftime("%s"), 
                        :feed_id
                    )
                EOM
            );

            $stmt->bindValue(':title',      $article->title,                     SQLITE3_TEXT);
            $stmt->bindValue(':url',        $article->link,                      SQLITE3_TEXT);
            $stmt->bindValue(':updated_at', $article->updatedAt->getTimestamp(), SQLITE3_INTEGER);
            $stmt->bindValue(':feed_id',    $feed_id,                            SQLITE3_INTEGER);

            $stmt->execute();
        }
    }
};
