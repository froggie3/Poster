<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast;

use DateTimeImmutable;
use DateTimeZone;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Iigau\Poster\Config;
use Iigau\Poster\Forecast\Response\Api\WeatherForecast;
use Iigau\Poster\Forecast\Response\Database\Telop;
use PDO;
use Psr\Log\LoggerInterface;

use Psr\Http\Client\ClientInterface;

/**
 * 必要な情報とインスタンスを管理するクラス
 *
 * @property ClientInterface $client
 * @property Discord $discord
 * @property LoggerInterface $logger
 * @property PDO $pdo
 * @property string $placeId
 * @property string $channelId
 * @property bool $isForced
 */
class Place
{
    /**
     * ロガー
     *
     * @var LoggerInterface
     */
    readonly public LoggerInterface $logger;

    /**
     * データベースハンドラー
     *
     * @var PDO
     */
    readonly public PDO $pdo;

    /**
     * HTTPクライアント
     *
     * @var ClientInterface
     */
    readonly public ClientInterface $client;

    /**
     * DiscordPHPのインスタンス
     *
     * @var Discord
     */
    readonly Discord $discord;

    /**
     * サーバーのチャンネル
     *
     * @var string
     */
    readonly string $channelId;

    /**
     * 天気予報の地域を識別するためのユニークな識別番号
     *
     * @var string
     */
    readonly string $placeId;

    /**
     * キャッシュを無効化するかどうか
     *
     * @var bool
     */
    readonly bool $isForced;

    /**
     * コンストラクタ
     *
     * @param ClientInterface $client
     * @param Discord $discord
     * @param LoggerInterface $logger
     * @param PDO $pdo
     * @param string $placeId
     * @param string $channelId
     * @param bool $isForced
     */
    function __construct(ClientInterface $client, Discord $discord, PDO $pdo, string $placeId, string $channelId, bool $isForced)
    {
        $this->client = $client;
        $this->discord = $discord;
        $this->logger = $discord->getLogger();
        $this->pdo = $pdo;
        $this->placeId = $placeId;
        $this->channelId = $channelId;
        $this->isForced = $isForced;
    }

    public function setPDO(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    public function getPDO(): PDO | false
    {
        if (is_null($this->pdo)) {
            return false;
        }
        return $this->pdo;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface | false
    {
        if (is_null($this->logger)) {
            return false;
        }
        return $this->logger;
    }

    public function setDiscord(Discord $discord): void
    {
        $this->discord = $discord;
    }

    public function getDiscord(): Discord | false
    {
        if (is_null($this->discord)) {
            return false;
        }
        return $this->discord;
    }
    /**
     * メイン処理
     *
     * @param Place $place
     * @return MessageBuilder
     */
    static function buildMessage(Place $place): MessageBuilder
    {
        $header = MessageHeader::createFromDB($place->pdo);
        $message = getMessagePartial($header, new MessageBuilder());
        $embed = getEmbedPartial($header, new Embed($place->discord));

        // Todo: handle HTTP error
        try {
            $json = null;
            if ($place->isForced) {
                $json = fetchCore($place);
            } else {
                if (!shouldUpdateAlternative($place)) {
                    $json = fetchCache($place);
                } else {
                    $json = fetch($place);
                }
            }
            $response = WeatherForecast::fromJson($json);
            $telop = getAssociatesFromTelop($place->pdo, $response->trf->forecast[0]->telop);
            return createMessageOnSuccess($header, $message, $embed, $response, $telop);
        } catch (\PDOException $e) {
            $place->logger->critical($e->getMessage());
        } catch (GuzzleException $e) {
            $place->logger->critical($e->getMessage());
        } catch (\JsonException $e) {
            $place->logger->critical($e->getMessage());
        } catch (\TypeError $e) {
            $place->logger->critical($e->getMessage());
        } catch (\Exception $e) {
            $place->logger->critical($e->getMessage());
        }
        return createMessageOnfailed($header, $message, $embed);
    }
}


/**
 * DB のキャッシュをアップデートする必要があるか判定
 *
 * @param Place
 * @return bool
 */
function shouldUpdate(Place $place): bool
{
    $query = <<<SQL
    SELECT
        (elapsedSeconds > ?) AS shouldUpdate
    FROM (
        SELECT
            (currentTime - updated_at) AS elapsedSeconds
        FROM (
            SELECT
                updated_at,
                strftime('%s', 'now') AS currentTime
            FROM
                weather_places
            WHERE
                place_id = ?
        )
    );
    SQL;

    $stmt = $place->pdo->prepare($query);

    $stmt->execute([
        Config::FORECAST_CACHE_LIFETIME,
        $place->placeId,
    ]);

    $result = $stmt->fetch(PDO::FETCH_OBJ);
    $shouldUpdate = $result->elapsedSeconds > Config::FORECAST_CACHE_LIFETIME;

    $place->logger->debug("getting cache info", [
        'placeId' => $place->placeId,
        'cacheLifetime' => Config::FORECAST_CACHE_LIFETIME,
        'shouldUpdate' => $shouldUpdate
    ]);

    return $shouldUpdate;
}


function fetchCache(Place $place): string
{
    $place->logger->debug("querying cache");
    $query = "SELECT cache FROM weather_places WHERE place_id = ?";
    $stmt = $place->pdo->prepare($query);
    $stmt->execute([$place->placeId]);
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    $content = $result->cache;
    return $content;
}

/**
 * DB のキャッシュをアップデートする必要があるか判定
 *
 * sqlite3 コマンドでは shouldUpdate = 1 となる query がなぜか 0 であり、
 * 腑に落ちないがクライアント側で暫定的処理してます
 *
 * @param Place
 * @return bool
 */
function shouldUpdateAlternative(Place $place): bool
{
    $query = <<<SQL
    SELECT
        elapsedSeconds,
    FROM (
        SELECT
            (currentTime - updated_at) AS elapsedSeconds
        FROM (
            SELECT
                updated_at,
                strftime('%s', 'now') AS currentTime
            FROM
                weather_places
            WHERE
                place_id = ?
        )
    );
    SQL;

    $stmt = $place->pdo->prepare($query);

    $stmt->execute([
        Config::FORECAST_CACHE_LIFETIME,
        $place->placeId,
    ]);

    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $shouldUpdate = $result->elapsedSeconds > Config::FORECAST_CACHE_LIFETIME;

    $place->logger->debug("getting cache info", [
        'placeId' => $place->placeId,
        'cacheLifetime' => Config::FORECAST_CACHE_LIFETIME,
        'elapsedSeconds' => $result->elapsedSeconds,
    ]);

    return $shouldUpdate;
}

function fetchCore(Place $place)
{
    $query = http_build_query([
        'uid'  => $place->placeId,
        'kind' => "web",
        'akey' => hash("md5", "nhk"),
    ]);
    $url = "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?$query";
    $request = new Request("GET", $url);
    $response = $place->client->sendRequest($request);
    $body = $response->getBody();
    $content = $body->getContents();
    /* caching */
    $queryCache = <<<SQL
    UPDATE
        weather_places
    SET
        cache = ?
    WHERE
        place_id = ?
    SQL;
    $stmtCache = $place->pdo->prepare($queryCache);
    $stmtCache->execute([
        Utils::JsonPrettyPrint($content),
        $place->placeId
    ]);
    // 最新更新時刻を更新
    $queryLocation = <<<SQL
    UPDATE
        weather_places
    SET
        updated_at = strftime('%s', 'now')
    WHERE
        place_id = ?
    SQL;
    $stmtLocation = $place->pdo->prepare($queryLocation);
    $stmtLocation->execute([$place->placeId]);
    /* end caching */

    return $content;
}


/**
 * DBから期限切れでないキャッシュの取得を試みる。
 * もし結果が返ってこなかったらNHK NEWS APIにアクセスして最新の天気を取得。
 * 取得後キャッシュを保存する。
 *
 * @param ClientInterface $client HTTPクライアント
 * @return string
 */
function fetch(Place $place): string
{
    $logger = $place->discord->getLogger();
    $logger->debug("no cache available for place id $place->placeId, fetching...");
    $content = fetchCore($place);
    return $content;
}

/**
 * Creates message on fail.
 *
 * @param MessageHeader $header
 * @param MessageBuilder $message
 * @param Embed $embed
 * @return MessageBuilder
 */
function createMessageOnfailed(MessageHeader $header, MessageBuilder $message, Embed $embed): MessageBuilder
{
    $dt = new DateTimeImmutable("now", new DateTimeZone("Asia/Tokyo"));
    $message
        ->setContent(sprintf("%s 時点の天気予報の取得に失敗しました", $dt->format("H:i")))
        ->addEmbed(
            $embed
                ->setTitle("取得失敗")
                ->setDescription("天気予報の取得に失敗しました")
                ->setTimestamp($dt->getTimestamp())
        );

    return $message;
}

/**
 * Resolve the information from a telop number from the db.
 *
 * @param PDO $pdo,
 * @param int $telopNumber
 * @return Telop
 */
function getAssociatesFromTelop(PDO $pdo, string $telopNumber): Telop
{
    $stmt = $pdo->prepare(
        "SELECT number,
        distinct_name AS distinctName,
        emoji_name AS emojiName,
        telop_filename AS telopFilename
        FROM telop WHERE number = ?"
    );

    $stmt->execute([$telopNumber]);
    $result = $stmt->fetchAll(PDO::FETCH_CLASS, Telop::class);

    return $result[0];
}


/**
 * Gets embed partials.
 *
 * @see https://discordapp.com/channels/115233111977099271/234582138740146176/1257752506117914704
 * @param MessageHeader $header
 * @param Embed $embed
 * @return Embed
 */
function getEmbedPartial(MessageHeader $header, Embed $embed): Embed
{
    // 青色（NHK天気・防災アプリの色）
    $colorCode = 0x0076d1;
    $embed
        ->setAuthor("NHK NEWS WEB", $header->avatarUrl, "https://www3.nhk.or.jp/news/")
        ->setColor($colorCode)
        ->setFooter("Deployed by Yokkin", $header->authorUrl)
    ;

    return $embed;
}

/**
 * Gets message partials.
 *
 * @param MessageHeader $header
 * @param MessageBuilder $message
 * @return MessageBuilder
 */
function getMessagePartial(MessageHeader $header, MessageBuilder $message): MessageBuilder
{
    $message
        ->setUsername("NHK NEWS WEB")
        ->setAvatarUrl($header->avatarUrl);

    return $message;
}

/**
 * メッセージを作成する
 *
 * @param MessageHeader $header
 * @param MessageBuilder $message
 * @param Embed $embed
 * @param WeatherForecast $response JSON 形式のレスポンスをオブジェクトに変換したもの
 * @param Telop $tp DB から拾ってきたテロップの関連情報
 * @return MessageBuilder
 */
function createMessageOnSuccess(MessageHeader $header, MessageBuilder $message, Embed $embed, WeatherForecast $response, Telop $tp): MessageBuilder
{
    $thumbnailUrl = "$header->baseUrl/{$tp->telopFilename}";
    $forecast = $response->trf->forecast[0];
    $embed
        ->setUrl("https://www.nhk.or.jp/kishou-saigai/city/weather/{$response->uid}")
        ->setTitle("きょうの天気予報")
        ->setTimestamp($response->trf->trfAtr->reportedDate->getTimestamp())
        ->setThumbnail($thumbnailUrl)
        ->setDescription("{$response->lv2Info->name}{$response->name}の天気予報です")
        ->addFieldValues("降水確率", sprintf(":umbrella: %d %%", $forecast->rainyDay), true)
        ->addFieldValues("最高気温", sprintf(":chart_with_upwards_trend: %d ℃ (%+d ℃)", $forecast->maxTemp, $forecast->maxTempDiff), true)
        ->addFieldValues("最低気温", sprintf(":chart_with_downwards_trend: %d ℃ (%+d ℃)", $forecast->minTemp, $forecast->minTempDiff), true)
        ->addFieldValues("天気", "{$tp->emojiName} {$tp->distinctName}")
    ;
    $message
        ->setContent(sprintf("天気でーす（データは %s 時点）", $response->trf->trfAtr->reportedDate->format("H:i")))
        ->addEmbed($embed)
    ;

    return $message;
}
