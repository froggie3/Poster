#!/usr/bin/env php
<?php

$digit = 24;
$itrs = 10;

foreach (range(1, $itrs) as $i) {
    $a = range(0, 1);
    foreach ($a as &$v) $v = randstr($digit, 2);
    [$x, $y] = $a;

    $s = randint(16);

    echo "INSERT INTO webhooks(id, title, url) VALUES($i, '$x', 'https://discord.com/api/webhooks/$s/$y') ON CONFLICT (url) DO NOTHING;\n";
}


foreach (range(1, $itrs) as $i) {
    $a = range(0, 1);
    foreach ($a as &$v) $v = randstr($digit, 2);
    [$x, $y] = $a;

    $p = randtime();

    echo "INSERT INTO feeds(id,title,url,updated_at) VALUES ($i, '$x', 'https://$y.com/rss', $p) ON CONFLICT (url) DO NOTHING;\n";
}

foreach (range(1, $itrs) as $i) {
    $a = range(0, 1);
    foreach ($a as &$v) $v = randIndex($itrs);
    [$x, $y] = $a;

    echo "INSERT INTO webhook_map_feed(webhook_id, feed_id) VALUES ($x, $y) ON CONFLICT (webhook_id, feed_id) DO NOTHING;\n";
}

genArticles();

foreach (range(1, $itrs * 3) as $i) {
    $x = randtime();

    $a = range(0, 1);
    foreach ($a as &$v) $v = randIndex($itrs);
    [$y, $z] = $a;

    echo "INSERT INTO post_history_feed (posted_at, webhook_id, article_id) VALUES ($x, $y, $z) ON CONFLICT (webhook_id, article_id) DO NOTHING;\n";
}

foreach (range(1, $itrs * 3) as $i) {
    $x = randtime();

    $a = range(0, 1);
    foreach ($a as &$v) $v = randIndex($itrs);
    [$y, $z] = $a;

    echo "INSERT INTO post_history_forecast (posted_at, webhook_id, location_id) VALUES ($x, $y, $z) ON CONFLICT (webhook_id, location_id) DO NOTHING;\n";
}


function randint($length)
{
    $str = "0123456789012345678901234567890123456789";
    $arr = str_split($str);
    shuffle($arr);
    $str = implode($arr);
    $res = substr($str, min(0, max(rand() % strlen($str), strlen($str) - $length)), $length);
    return $res;
}

function randstr($length, $type)
{
    $strset = [
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
        "012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789",
        "0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz",
    ];
    $str = $strset[$type];
    $arr = str_split($str);
    shuffle($arr);
    $str = implode($arr);
    $res = substr($str, min(0, max(rand() % strlen($str), strlen($str) - $length)), $length);
    return $res;
}

function randtime(): int
{
    return rand() % 4294967295;
}

function genArticles()
{
    global $itrs;
    global $digit;
    foreach (range(1, $itrs) as $i) {
        $a = range(0, 1);
        foreach ($a as &$v) $v = randstr($digit, 2);
        [$x, $y] = $a;

        $rands = range(0, 2);
        foreach ($rands as &$v) $v = randtime($digit);
        sort($rands);
        [$p, $q] = $rands;

        $r = max(1, rand() % $itrs + 1);

        echo "INSERT INTO articles VALUES ($i, '$x', 'https://$y.com', $r, $p);\n";
    }
}

function randIndex($itrs): int
{
    return max(1, rand() % $itrs + 1);
}
?>