<?php

declare(strict_types=1);

namespace App;

class Constants
{
    // 環境変数として設定する変数のキー名
    const WEBHOOK_URL_KEY = 'WEBHOOK_URL';
    const PLACE_ID_KEY = 'PLACE_ID';

    // ログ用のモジュール名の設定
    const MODULE_FEED_FETCHER = 'FeedFetcher';
    const MODULE_FORECAST_FETCHER = "ForcastFetcher";
    const MODULE_FORECAST_POSTER = "ForcastPoster";
    const MODULE_HTTP = "Http";
}
