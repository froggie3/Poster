<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast;

use Iigau\Poster\Config;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use PDO;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Monolog\Logger;
use GuzzleHttp\Client;

class Utils
{
    /**
     * verifies whether $epochtime and $time are the same time.
     * 
     * @param int $epochTime 基準時刻。
     * @param int $hour 比較する時間。
     * @return bool
     */
    static function compareHour(int $epochTime, int $hour): bool
    {
        return 3600 * $hour === ($epochTime + 32400) % 86400;
    }

    /**
     * verifies current hour is $hour.
     * 
     * @param int $hour 比較する時間。
     * @return bool
     */
    static function isCurrentHour(int $hour): bool
    {
        return self::compareHour(time(), $hour);
    }

    /**
     * Prepare stream handler for Monolog.
     * 
     * @return StereamHandler StreamHandler.
     */
    static function prepareStreamHandler(): StreamHandler
    {
        $dateFormat = "Y-m-d\TH:i:sP";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, false, true, true);
        $stream = new StreamHandler(CONFIG::LOGGING_PATH, Config::MONOLOG_LOG_LEVEL);
        $stream->setFormatter($formatter);

        return $stream;
    }

    /**
     * A class that applies the content of command-line flags, and returns PDO instance.
     * 
     * @return PDO PDO.
     */
    static function preparePdo(): PDO
    {
        $environmentVariableName = "DATABASE_PATH";

        $databasePath = getenv($environmentVariableName) ?: Config::DATABASE_PATH; // fallback
        $dsn = "sqlite:$databasePath";

        return new \PDO($dsn);
    }

    /**
     * Prepare HTTP Client.
     * Reference: https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware
     * 
     * @return Client HTTP Client.
     */
    static function prepareHttpClient(array $headers = []): Client
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $addHeader =
            fn($header, $value) =>
            fn(callable $handler) =>
            fn(RequestInterface $request, array $options) =>
            $handler($request->withHeader($header, $value), $options);

        foreach ($headers as $header => $value) {
            $stack->push($addHeader($header, $value));
        }
        $client = new Client([
            'timeout' => Config::CONNECTION_TIMEOUT_SECONDS,
            'handler' => $stack,
            'track_redirects' => true,
            'allow_redirects' => true,
        ]);

        return  $client;
    }
    /**
     * 設定テーブルの中の特定のキーを探して値を取り出す
     * 
     * @param PDO Database.
     * @param string $key キー名を探します
     * @return string string.
     */
    static function extractTasks(PDO $pdo, string $key): string
    {
        $query = "SELECT
            title AS channelName,
            p.name as placeName,
            a.channel_id AS channelId,
            a.place_id AS placeId,
            p.updated_at AS updatedAt 
        FROM registers AS a
        INNER JOIN channels AS c ON a.channel_id = c.channel_id
        INNER JOIN weather_places AS p ON p.place_id = a.place_id
        WHERE enabled = 1
        LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return $result->value;
    }

    /**
     * 設定テーブルの中の特定のキーを探して値を取り出す
     * 
     * @param PDO Database.
     * @param string $key キー名を探します
     * @return string string.
     */
    static function getSettingValue(PDO $pdo, string $key): string
    {
        $query = "SELECT value FROM settings WHERE key = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return $result->value;
    }
}
