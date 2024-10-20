<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast;

use Iigau\Poster\Config;

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
     * @param \Monolog\Level ログレベル
     * @return \Monolog\Handler\StreamHandler StreamHandler.
     */
    static function prepareStreamHandler($loglevel = \Monolog\Level::Info): \Monolog\Handler\StreamHandler
    {
        $dateFormat = "Y-m-d\TH:i:sP";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new \Monolog\Formatter\LineFormatter($output, $dateFormat, false, true, true);
        $stream = new \Monolog\Handler\StreamHandler(CONFIG::LOGGING_PATH, $loglevel);
        $stream->setFormatter($formatter);

        return $stream;
    }

    /**
     * A class that applies the content of command-line flags, and returns PDO instance.
     *
     * @return \PDO PDO.
     */
    static function preparePdo(string $databasePath): \PDO
    {
        $dsn = "sqlite:$databasePath";
        return new \PDO($dsn);
    }

    /**
     * Prepare HTTP Client.
     * Reference: https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware
     *
     * @return \GuzzleHttp\Client HTTP Client.
     */
    static function prepareHttpClient(array $headers = []): \Psr\Http\Client\ClientInterface
    {
        $stack = new \GuzzleHttp\HandlerStack();
        $stack->setHandler(new \GuzzleHttp\Handler\CurlHandler());

        $addHeader =
            fn($header, $value) =>
            fn(callable $handler) =>
            fn(\Psr\Http\Message\RequestInterface $request, array $options) =>
            $handler($request->withHeader($header, $value), $options);

        foreach ($headers as $header => $value) {
            $stack->push($addHeader($header, $value));
        }
        $client = new \GuzzleHttp\Client([
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
     * @param \PDO Database.
     * @param string $key キー名を探します
     * @return string string.
     */
    static function extractTasks(\PDO $pdo, string $key): string
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
     * @param \PDO Database.
     * @param string $key キー名を探します
     * @return string string.
     */
    static function getSettingValue(\PDO $pdo, string $key): string
    {
        $query = "SELECT value FROM settings WHERE key = ?";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return $result->value;
    }

    /**
     * ChannelIdを取得する
     * PlaceIdを取得する
     */
    static function getPlaceIdChannelId(\PDO $pdo): array
    {
        $sql = "SELECT title AS place_name, p.name, a.channel_id AS channelId, a.place_id AS placeId, p.updated_at FROM registers AS a INNER JOIN channels AS c ON a.channel_id = c.channel_id INNER JOIN weather_places AS p ON p.place_id = a.place_id WHERE enabled = 1 LIMIT 1;";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return [$result[0]->placeId, $result[0]->channelId];
    }

    /**
     * 有効な channelId を 1 件取得する
     */
    static function getChannelId(\PDO $pdo): string
    {
        $sql = "SELECT channel_id AS channelId FROM registers WHERE enabled = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return $result->channelId;
    }

    /**
     * 有効な placeId を 1 件取得する
     */
    static function getPlaceId(\PDO $pdo): string
    {
        $sql = "SELECT place_id AS placeId FROM registers WHERE enabled = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        return $result->placeId;
    }

    static function getChannels(\PDO $pdo): array
    {
        $sql = "SELECT DISTINCT channel_id AS channelId FROM registers WHERE enabled = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

        return array_map(fn($r) => $r->channelId, $result);
    }

    /**
     * JSONを整形して出力
     */
    static function JsonPrettyPrint(string $content): string
    {
        return json_encode(json_decode($content, false, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT);
    }
}
