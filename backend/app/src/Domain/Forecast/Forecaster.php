<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use App\Config;
use App\Data\CommandFlags\Flags;
use App\Utils\DiscordPostPoster;
use App\Utils\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;

use App\Data\Discord\Author;
use App\Data\Discord\DiscordPost;
use App\Data\Discord\Embed;
use App\Data\Discord\Field;
use App\Data\Discord\Footer;
use App\Data\Discord\Thumbnail;
use App\Utils\DtoBase;

class Telop
{
    # 天気
    public string $distinct_name;
    # 絵文字
    public string $emoji_name;
    # アプリからとってきたテロップのファイル名
    public string $telop_filename;
    # NHK NEWS WEB のテロップを親であり、-1としたとき、子であるならその親の番号
    public int $telop_id_parent;
    public TelopImageUsed $type_exists_in;

    public function __construct(
        string $distinctName,
        string $emojiName,
        string $telopFilename,
        int $telopIdParent,
        TelopImageUsed $typeExistsIn,
    ) {
        $this->distinct_name = $distinctName;
        $this->emoji_name = $emojiName;
        $this->telop_filename = $telopFilename;
        $this->telop_id_parent = $telopIdParent;
        $this->type_exists_in = $typeExistsIn;
    }
}

class TelopImageUsed
{
    # Web版に特有のテロップか
    public bool $web = false;
    # アプリ版に特有のテロップか
    public bool $app = false;

    public function __construct(bool $web, bool $app)
    {
        $this->web = $web;
        $this->app = $app;
    }
}

class ForecastDto extends DtoBase
{
    public int $webhookId;
    public int $locationId;
    public string $placeId;
    public string $webhookUrl;

    public string $content;
    public \stdClass $response;

    public array $ForecastTelops;
    private \PDO $pdo;

    public function __construct(\PDO $pdo, array $properties)
    {
        parent::__construct($properties);
        $this->pdo = $pdo;
        $this->toObject();
    }

    /**
     * Convert raw response to stdClass.
     */
    protected function toObject()
    {
        $this->response = json_decode($this->content);
    }

    protected function processOne(
        \stdClass $res,
        Telop $tp,
        \stdClass $fc,
    ): Forecast {
        $forecast = new Forecast(
            $res->uid,
            "{$res->lv2_info->name}{$res->name}",
            $res->trf->trf_atr->reported_date,
            $fc->max_temp,
            $fc->max_temp_diff,
            $fc->min_temp,
            $fc->min_temp_diff,
            $fc->rainy_day,
            $tp->distinct_name,
            $tp->emoji_name,
            $tp->telop_filename,
        );

        return $forecast;
    }

    /**
     * Make a record of what was done into the database.
     */
    public function addHistory(): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT OR IGNORE INTO post_history_forecast (posted_at, webhook_id, location_id)
            VALUES (strftime('%s', 'now'), :wid, :lid);",
        );

        $stmt->bindValue(":wid", $this->webhookId, \PDO::PARAM_INT);
        $stmt->bindValue(":lid", $this->locationId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }

    /**
     * processThreeDays
     *
     * @return array[Forecast]
     */
    protected function processThreeDays(): array
    {
        $forecastThreeDays = [];

        foreach ($this->response->trf->forecast as $day => $forecast) {
            if (!array_key_exists($forecast->telop, $this->ForecastTelops)) {
                throw new \Exception("Unsupported telop");
            }

            $forecastThreeDays[] = $this->processOne(
                $this->response,
                $this->ForecastTelops[$forecast->telop],
                $forecast,
            );
        }

        return $forecastThreeDays;
    }

    public function process(): Forecast
    {
        $this->ForecastTelops = [
            100 => new Telop(
                "晴れ",
                ":sunny:",
                "tlp100.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            101 => new Telop(
                "晴れ時々くもり",
                ":partly_sunny:",
                "tlp101.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            102 => new Telop(
                "晴れ一時雨",
                ":white_sun_rain_cloud:",
                "tlp102.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            103 => new Telop(
                "晴れ時々雨",
                ":white_sun_rain_cloud:",
                "tlp103.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            104 => new Telop(
                "晴れ一時雪",
                ":white_sun_rain_cloud:",
                "tlp105.png",
                -1,
                new TelopImageUsed(true, false),
            ),
            105 => new Telop(
                "晴れ一時雪",
                ":white_sun_rain_cloud:",
                "tlp105.png",
                104,
                new TelopImageUsed(false, true),
            ),
            110 => new Telop(
                "晴れのちくもり",
                ":white_sun_cloud:",
                "tlp110.png",
                -1,
                new TelopImageUsed(false, true),
            ),
            111 => new Telop(
                "晴れのちくもり",
                ":white_sun_cloud:",
                "tlp110.png",
                110,
                new TelopImageUsed(true, false),
            ),
            113 => new Telop(
                "晴れのち雨",
                ":white_sun_rain_cloud:",
                "tlp113.png",
                -1,
                new TelopImageUsed(false, true),
            ),
            114 => new Telop(
                "晴れのち雨",
                ":white_sun_rain_cloud:",
                "tlp113.png",
                113,
                new TelopImageUsed(true, false),
            ),
            115 => new Telop(
                "晴れのち雪",
                ":white_sun_rain_cloud:",
                "tlp115.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            200 => new Telop(
                "くもり",
                ":cloud:",
                "tlp200.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            201 => new Telop(
                "くもり時々晴れ",
                ":partly_sunny:",
                "tlp201.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            202 => new Telop(
                "くもり一時雨",
                ":cloud_rain:",
                "tlp202.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            203 => new Telop(
                "くもり時々雨",
                ":cloud_rain:",
                "tlp203.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            204 => new Telop(
                "くもり一時雪",
                ":cloud_rain:",
                "tlp205.png",
                -1,
                new TelopImageUsed(true, false),
            ),
            205 => new Telop(
                "くもり一時雪",
                ":cloud_rain:",
                "tlp205.png",
                204,
                new TelopImageUsed(false, true),
            ),
            210 => new Telop(
                "くもりのち晴れ",
                ":white_sun_cloud:",
                "tlp210.png",
                211,
                new TelopImageUsed(false, true),
            ),
            211 => new Telop(
                "くもりのち晴れ",
                ":white_sun_cloud:",
                "tlp210.png",
                -1,
                new TelopImageUsed(true, false),
            ),
            213 => new Telop(
                "くもりのち雨",
                ":cloud_rain:",
                "tlp213.png",
                -1,
                new TelopImageUsed(false, true),
            ),
            214 => new Telop(
                "くもりのち雨",
                ":cloud_rain:",
                "tlp213.png",
                213,
                new TelopImageUsed(true, false),
            ),
            215 => new Telop(
                "くもりのち雪",
                ":cloud_rain:",
                "tlp215.png",
                -1,
                new TelopImageUsed(false, true),
            ),
            217 => new Telop(
                "くもりのち雪",
                ":cloud_rain:",
                "tlp215.png",
                215,
                new TelopImageUsed(true, false),
            ),
            300 => new Telop(
                "雨",
                ":cloud_rain:",
                "tlp300.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            301 => new Telop(
                "雨時々晴れ",
                ":white_sun_rain_cloud:",
                "tlp301.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            302 => new Telop(
                "雨一時くもり",
                ":white_sun_small_cloud:",
                "tlp302.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            303 => new Telop(
                "雨時々雪",
                ":cloud_snow:",
                "tlp303.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            308 => new Telop(
                "暴風雨",
                ":cloud_rain:",
                "tlp308.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            311 => new Telop(
                "雨のち晴れ",
                ":white_sun_rain_cloud:",
                "tlp311.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            313 => new Telop(
                "雨のちくもり",
                ":cloud_rain:",
                "tlp313.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            315 => new Telop(
                "雨のち雪",
                ":cloud_snow:",
                "tlp315.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            400 => new Telop(
                "雪",
                ":snowflake:",
                "tlp400.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            401 => new Telop(
                "雪時々晴れ",
                ":cloud_snow:",
                "tlp401.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            402 => new Telop(
                "雪時々やむ",
                ":cloud_snow:",
                "tlp402.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            403 => new Telop(
                "雪時々雨",
                ":cloud_snow:",
                "tlp403.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            407 => new Telop(
                "暴風雪",
                ":cloud_snow:",
                "tlp407.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            409 => new Telop(
                "雪時々雨",
                ":cloud_snow:",
                "tlp409.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            411 => new Telop(
                "雪のち晴れ",
                ":white_sun_rain_cloud:",
                "tlp411.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            413 => new Telop(
                "雪のちくもり",
                ":cloud_snow:",
                "tlp413.png",
                -1,
                new TelopImageUsed(true, true),
            ),
            414 => new Telop(
                "雪のち雨",
                ":cloud_snow:",
                "tlp414.png",
                -1,
                new TelopImageUsed(true, true),
            ),
        ];

        $data = $this->processThreeDays()[0];

        assert($data instanceof Forecast);
        return $data;
    }
}

/**
 * Data class that contains the weather for one day
 */
class Forecast
{
    public string $locationUid;
    public string $locationName; // foo県bar市baz区
    public string $forecastDate;
    public string $maxTemp;
    public string $maxTempDiff;
    public string $minTemp;
    public string $minTempDiff;
    public string $rainyDay;
    public string $weather;
    public string $weatherEmoji;
    public string $telopFile;

    public string $authorUrl;
    public string $avatarUrl;
    public string $thumbnailUrl;
    public string $locationUrl;

    public function __construct(
        string $locationUid,
        string $locationName,
        string $forecastDate,
        string $maxTemp,
        string $maxTempDiff,
        string $minTemp,
        string $minTempDiff,
        string $rainyDay,
        string $weather,
        string $weatherEmoji,
        string $telopFile,
    ) {
        $this->locationUid = $locationUid;
        $this->locationName = $locationName;
        $this->forecastDate = $forecastDate;
        $this->maxTemp = $maxTemp;
        $this->maxTempDiff = $maxTempDiff;
        $this->minTemp = $minTemp;
        $this->minTempDiff = $minTempDiff;
        $this->rainyDay = $rainyDay;
        $this->weather = $weather;
        $this->weatherEmoji = $weatherEmoji;
        $this->telopFile = $telopFile;
    }

    public function getLocationUrl(): string
    {
        return "https://www.nhk.or.jp/kishou-saigai/city/weather/{$this->locationUid}";
    }

    public function toDiscordPost(): DiscordPost
    {
        $this->authorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
        $this->avatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/{$this->telopFile}";

        return new DiscordPost([
            "content" => sprintf(
                "天気でーす（データは %s 時点）",
                (new \DateTimeImmutable($this->forecastDate))->format("H:i"),
            ),
            "username" => "NHK NEWS WEB",
            "avatar_url" => $this->avatarUrl,
            "embeds" => [
                new Embed([
                    "title" => "きょうの天気予報",
                    "description" => "{$this->locationName}の天気予報です",
                    "url" => $this->getLocationUrl(),
                    "timestamp" => $this->forecastDate,
                    "color" => 0x0076d1,
                    "thumbnail" => new Thumbnail([
                        "url" => $this->thumbnailUrl,
                    ]),
                    "footer" => new Footer([
                        "text" => "Deployed by Yokkin",
                        "icon_url" => $this->authorUrl,
                    ]),
                    "author" => new Author([
                        "name" => "NHK NEWS WEB",
                        "url" => "https://www3.nhk.or.jp/news/",
                        "icon_url" => $this->avatarUrl,
                    ]),
                    "fields" => [
                        new Field([
                            "name" => "天気",
                            "value" =>
                            $this->weatherEmoji . " " . $this->weather,
                            "inline" => false,
                        ]),
                        new Field([
                            "name" => "最高気温",
                            "value" => sprintf(
                                ":chart_with_upwards_trend: %d ℃ (%+d ℃)",
                                $this->maxTemp,
                                $this->maxTempDiff,
                            ),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "最低気温",
                            "value" => sprintf(
                                ":chart_with_downwards_trend: %d ℃ (%+d ℃)",
                                $this->minTemp,
                                $this->minTempDiff,
                            ),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "降水確率",
                            "value" => sprintf(
                                ":umbrella: %d %%",
                                $this->rainyDay,
                            ),
                            "inline" => true,
                        ]),
                    ],
                ]),
            ],
        ]);
    }
}

/**
 * A class that handles the queue.
 */
class ForecastConsumer
{
    private \PDO $db;
    private DiscordPostPoster $poster;
    private Logger $logger;
    private array $queue;
    private int $queueCount;

    public function __construct(
        Logger $logger,
        \PDO $db,
        DiscordPostPoster $poster,
        array $queue,
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->poster = $poster;
        $this->queue = $queue;
        $this->queueCount = count($this->queue);
    }

    /**
     * Whether the queue has any elements.
     */
    protected function runnable(): bool
    {
        return !empty($this->queueCount);
    }

    /**
     * Does jobs in a queue.
     */
    public function process(): void
    {
        $this->logger->info("Processing queue", [
            "in queue" => count($this->queue),
        ]);

        foreach ($this->queue as $object) {
            assert($object instanceof ForecastDto);
            $this->queueCount--;

            $forecast = $object->process();
            $discordPost = $forecast->toDiscordPost();
            $this->logger->debug("Post generation finished");

            $this->poster->post($discordPost, $object->webhookUrl);
            $this->logger->debug("Message sent");

            $object->addHistory();

            $this->logger->debug("Added to post history");

            if ($this->runnable()) {
                $this->logger->debug("Waiting for the next request", [
                    "seconds" => \App\Config::INTERVAL_REQUEST_SECONDS,
                ]);
                sleep(\App\Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }
        }

        $this->logger->info("Finished posting");
    }
}


class ForecastFetcherQuery
{
    public string $uid;
    public string $kind = "web";
    public string $akey;

    public function __construct(string $placeId)
    {
        $this->uid = $placeId;
        $this->akey = hash("md5", "nhk");
    }

    public function buildQuery(): string
    {
        return http_build_query($this);
    }
}

class Places extends \ArrayObject
{
    private Logger $logger;
    private \PDO $pdo;
    private Flags $flags;
    private Client $client;
    private bool $alreadyRetrieved = false;

    public function __construct(
        Logger $logger,
        Flags $flags,
        \PDO $pdo,
        Client $client,
    ) {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->pdo = $pdo;
        $this->client = $client;
    }

    public function needsUpdate()
    {
        if (!$this->alreadyRetrieved) {
            throw new \Error(
                "update check should be done after cache checking",
            );
        }
        return !empty($this);
    }

    /**
     * If the lifetime of the cache is longer than
     * what is configured in `Config.php`, update the cache.
     */
    public function getPlacesNeedsUpdate()
    {
        if ($this->flags->isForced()) {
            $this->logger->alert("'--force-update' is set");
        }

        $stmt = $this->pdo->prepare(
            $this->flags->isForced()
                ? "SELECT id, place_id FROM locations"
                : "SELECT id, place_id FROM locations
                WHERE strftime('%s', 'now') - updated_at >= :cache",
        );

        if (!$this->flags->isForced()) {
            $stmt->bindValue(
                ":cache",
                Config::FEED_CACHE_LIFETIME,
                \PDO::PARAM_INT,
            );
        }
        $stmt->execute();

        $this->alreadyRetrieved = true;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC)
            as ["id" => $locId, "place_id" => $placeId]) {
            $this[] = new Place(
                $this->logger,
                $this->pdo,
                $this->client,
                $locId,
                $placeId,
            );
        }

        return $this;
    }
}

class Place
{
    public int $locId;
    public string $placeId;
    private Logger $logger;
    private \PDO $pdo;
    private Client $client;

    public function __construct(
        Logger $logger,
        \PDO $pdo,
        Client $client,
        int $locId,
        string $placeId,
    ) {
        $this->logger = $logger;
        $this->pdo = $pdo;
        $this->locId = $locId;
        $this->placeId = $placeId;
        $this->client = $client;
    }

    /**
     * Updates the last updated time cache of $locationId.
     */
    private function updateLocations(): bool
    {
        $query = "UPDATE
                locations
            SET
                updated_at = strftime('%s', 'now')
            WHERE id = :locId";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(":locId", $this->locId, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Caches the JSON response into the database.
     */
    private function saveCache(string $content): bool
    {
        $query = "INSERT OR REPLACE INTO cache_forecast (location_id, content)
            VALUES (:id, :res);";

        $stmt = $this->pdo->prepare($query);

        $stmt->bindValue(":id", $this->locId, \PDO::PARAM_INT);
        $stmt->bindValue(":res", $content, \PDO::PARAM_STR);

        // $this->logger->info($stmt->queryString);
        $result = $stmt->execute();

        return $result;
    }

    /**
     * Get and returns the data in JSON from NHK NEWS API.
     */
    private function fetch(): string
    {
        $query = (new ForecastFetcherQuery($this->placeId))->buildQuery();
        $this->logger->debug("Query built", ["query" => $query]);
        $request = new Request(
            "GET",
            "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?$query",
        );

        $this->logger->debug("Requesting");
        $http = new Http($this->logger, $this->client);
        $res = $http->sendRequest($request);
        $this->logger->debug("Got response", ["bytes" => strlen($res)]);

        return $res;
    }

    public function updateCache()
    {
        try {
            $res = $this->fetch();

            // on success
            $this->saveCache($res);

            $updatedResult = $this->updateLocations($this->locId);
            if ($updatedResult) {
                $this->logger->debug("Updated last updated time");
            }
            $this->logger->debug("Fetched forecast", [
                "placeId" => $this->placeId,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning(
                "Failed to retrieve the weather in $this->placeId",
                ["exception" => $e],
            );
        }
    }
}

/**
 * A class that handles the whole process of retrieving and posting forecast.
 */
class Forecaster
{
    private Logger $logger;
    private \PDO $pdo;
    private Flags $flags;
    private ForecastConsumer $poster;
    private Client $client;
    private Places $places;

    public function __construct(
        Logger $logger,
        Flags $flags,
        \PDO $pdo,
        Client $client,
    ) {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->client = $client;
        $this->pdo = $pdo;
    }

    /**
     * Retrieves the cache from Database and add them to the queue.
     */
    private function retrieveForecastFromCache(): array
    {
        $query = "SELECT
                webhooks.id as webhookId,
                locations.id as locationId,
                locations.place_id as placeId,
                webhooks.url as webhookUrl,
                cache_forecast.content as content
            FROM
                webhook_map_forecast
            INNER JOIN locations
                ON webhook_map_forecast.location_id = locations.id
            INNER JOIN webhooks
                ON webhooks.id = webhook_map_forecast.webhook_id
            INNER JOIN cache_forecast
                ON cache_forecast.location_id = locations.id
            WHERE
                webhook_map_forecast.enabled = 1";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $queue = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $queue[] = new ForecastDto($this->pdo, $row);
        }

        return $queue;
    }

    public function process(): void
    {
        $this->places = new Places(
            $this->logger,
            $this->flags,
            $this->pdo,
            $this->client,
        );
        $this->places->getPlacesNeedsUpdate();

        if (!$this->places->needsUpdate()) {
            $this->logger->info("There is no updates found. Exiting.");
            return;
        }
        $this->logger->info("Update needed");

        foreach ($this->places as $place) {
            assert($place instanceof Place);
            $place->updateCache();
        }

        $queue = $this->retrieveForecastFromCache();

        $this->poster = new ForecastConsumer(
            $this->logger,
            $this->pdo,
            new DiscordPostPoster($this->logger, $this->client),
            $queue,
        );

        $this->poster->process();
    }
}
