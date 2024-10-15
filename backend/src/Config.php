<?php

declare(strict_types=1);

namespace Iigau\Poster;

/**
 * 設定など
 * 
 * @property int CONNECTION_TIMEOUT_SECONDS
 * @property int INTERVAL_REQUEST_SECONDS  
 * @property int FORECAST_CACHE_LIFETIME
 * @property int MAX_REQUEST_RETRY
 * @property int MONOLOG_LOG_LEVEL
 */
class Config
{
    /**
     * キャッシュを無効化し、強制的に更新するコマンドラインフラグ
     * 
     * @var string
     */
    public const CONNECTION_TIMEOUT_SECONDS = 10;

    /**
     * HTTP リクエストの時間間隔
     * 
     * @var string
     */
    public const INTERVAL_REQUEST_SECONDS   = 1;

    /**
     * キャッシュの寿命
     * 
     * @var int
     */
    public const FORECAST_CACHE_LIFETIME = 3600;

    /**
     * 最大試行回数
     * 
     * @var int
     */
    public const MAX_REQUEST_RETRY = 5;

    /**
     * ログレベル
     * 
     * @var \Monolog\Level
     */
    public const MONOLOG_LOG_LEVEL = \Monolog\Level::Info;

    /**
     * 環境変数で持っておくべき？
     * 
     * @var string
     */
    public const DATABASE_PATH = __DIR__ . '/../../sqlite.db';

    /**
     * ログのパス
     * 
     * @var string
     */
    public const LOGGING_PATH  = "php://stdout";
}
