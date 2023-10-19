#!/usr/bin/env php
<?php
class Telop
{
    public const TelopData = [
        100 => ["晴れ", ":sunny:"],
        101 => ["晴れ時々くもり", ":partly_sunny:"],
        102 => ["晴れ一時雨", ":white_sun_rain_cloud:"],
        103 => ["晴れ時々雨", ":white_sun_rain_cloud:"],
        105 => ["晴れ一時雪", ":white_sun_rain_cloud:"],
        110 => ["晴れのちくもり", ":white_sun_cloud:"],
        111 => ["晴れのちくもり", ":white_sun_cloud:"],  # dup: 110
        113 => ["晴れのち雨", ":white_sun_rain_cloud:"],
        114 => ["晴れのち雨", ":white_sun_rain_cloud:"],  # dup: 113
        115 => ["晴れのち雪", ":white_sun_rain_cloud:"],
        200 => ["くもり", ":cloud:"],
        201 => ["くもり時々晴れ", ":partly_sunny:"],
        202 => ["くもり一時雨", ":cloud_rain:"],
        203 => ["くもり時々雨", ":cloud_rain:"],
        205 => ["くもり一時雪", ":cloud_rain:"],
        210 => ["くもりのち晴れ", ":white_sun_cloud:"],
        211 => ["くもりのち晴れ", ":white_sun_cloud:"],  # dup: 210
        213 => ["くもりのち雨", ":cloud_rain:"],
        214 => ["くもりのち雨", ":cloud_rain:"],  # dup: 213
        215 => ["くもりのち雪", ":cloud_rain:"],
        300 => ["雨", ":cloud_rain:"],
        301 => ["雨時々晴れ", ":white_sun_rain_cloud:"],
        302 => ["雨一時くもり", ":white_sun_small_cloud:"],
        303 => ["雨時々雪", ":cloud_snow:"],
        308 => ["暴風雨", ":cloud_rain:"],
        311 => ["雨のち晴れ", ":white_sun_rain_cloud:"],
        313 => ["雨のちくもり", ":cloud_rain:"],
        315 => ["雨のち雪", ":cloud_snow:"],
        400 => ["雪", ":snowflake:"],
        401 => ["雪時々晴れ", ":cloud_snow:"],
        402 => ["雪時々やむ", ":cloud_snow:"],
        403 => ["雪時々雨", ":cloud_snow:"],
        407 => ["暴風雪", ":cloud_snow:"],
        409 => ["雪時々雨", ":cloud_snow:"],
        411 => ["雪のち晴れ", ":white_sun_rain_cloud:"],
        413 => ["雪のちくもり", ":cloud_snow:"],
        414 => ["雪のち雨", ":cloud_snow:"]
    ];
}

class TenkiFetch
{
    private $query;
    private $weatherData;

    public function __construct($uid)
    {
        $this->query = http_build_query([
            'uid' => $uid,
            'kind' => "web",
            'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
        ]);
        $this->weatherData = [];
    }

    public function fetch()
    {
        $url = "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?" . $this->query;
        $response = file_get_contents($url);
        if ($response) {
            $this->weatherData = json_decode($response, true);
            return $this->weatherData;
        }
        return [];
    }
}

class WebhookPosting
{
    protected const Headers = [
        "Content-Type: application/json",
        "User-Agent: Mozilla/5.0"
    ];
    protected $webhookUrl;

    public function __construct()
    {
    }

    protected function post($url, $payload, $headers)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload
            ]
        ]);
        $response = file_get_contents($url, false, $context);
        return $response;
    }
}

class WebhookNHKNews extends WebhookPosting
{
    private const AuthorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
    private const AvatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
    private $content;
    private $weather;
    private $locationUrl;
    private $thumbnailUrl;
    private $timestamp;

    public function __construct($url, $weather)
    {
        $this->webhookUrl = $url;
        $this->content = "天気でーす";
        $this->weather = $weather;
    }

    public function send()
    {
        $data = $this->preparePayload($this->weather);
        $payload = json_encode($data);
        $response = $this->post($this->webhookUrl, $payload, self::Headers);
        if ($response) {
            return true;
        }
        return false;
    }

    protected function preparePayload($weather)
    {
        $this->locationUrl = "https://www.nhk.or.jp/kishou-saigai/city/weather/" . $weather->locationUid;
        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/tlp" . $weather->telop . ".png";
        $this->timestamp = $weather->forecastDate;

        $payload = [
            "content" => $this->content,
            "username" => "NHK NEWS WEB",
            "avatar_url" => self::AvatarUrl,
            "embeds" => [
                [
                    "title" => "きょうの天気予報",
                    "description" => $weather->locationName . "の天気予報です",
                    "url" => $this->locationUrl,
                    "timestamp" => $this->timestamp,
                    "color" => 0x0076d1,
                    "image" => ["url" => "https://www3.nhk.or.jp/weather/tenki/tenki_01.jpg"],
                    "thumbnail" => ["url" => $this->thumbnailUrl],
                    "footer" => [
                        "text" => "Deployed by Yokkin",
                        "icon_url" => self::AuthorUrl,
                    ],
                    "author" => [
                        "name" => "NHK NEWS WEB",
                        "url" => "https://www3.nhk.or.jp/news/",
                        "icon_url" => self::AvatarUrl,
                    ],
                    "fields" => [
                        [
                            "name" => "天気",
                            "value" => $weather->weatherEmoji . " " . $weather->weather,
                            "inline" => false,
                        ],
                        [
                            "name" => "最高気温",
                            "value" => ":chart_with_upwards_trend: " . $weather->maxTemp . " ℃ " . sprintf("(%+d ℃)", $weather->maxTempDiff),
                            "inline" => true,
                        ],
                        [
                            "name" => "最低気温",
                            "value" => ":chart_with_downwards_trend: " . $weather->minTemp . " ℃ " . sprintf("(%+d ℃)", $weather->minTempDiff),
                            "inline" => true,
                        ],
                        [
                            "name" => "降水確率",
                            "value" => ":umbrella: " . $weather->rainyDay . " %",
                            "inline" => true,
                        ],
                    ],
                ],
            ],
        ];

        return $payload;
    }
}

class Weather
{
    public $locationUid;
    public $locationName;
    public $forecastDate;
    public $maxTemp;
    public $maxTempDiff;
    public $minTemp;
    public $minTempDiff;
    public $rainyDay;
    public $telop;
    public $weather;
    public $weatherEmoji;

    public function __construct($uid, $weatherData)
    {
        $pref = $weatherData['lv2_info']['name'];
        $district = $weatherData['name'];
        $this->locationUid = $uid;
        $this->locationName = $pref . $district;

        $forecastThreeDays = $weatherData['trf']['forecast'];
        $forecastToday = $forecastThreeDays[0];

        $this->forecastDate = $forecastToday['forecast_date'];
        $this->maxTemp = $forecastToday['max_temp'];
        $this->maxTempDiff = $forecastToday['max_temp_diff'];
        $this->minTemp = $forecastToday['min_temp'];
        $this->minTempDiff = $forecastToday['min_temp_diff'];
        $this->rainyDay = $forecastToday['rainy_day'];

        $this->telop = $forecastToday['telop'];
        [$this->weather, $this->weatherEmoji] = Telop::TelopData[$this->telop];
    }
}

if (!$env = parse_ini_file('.env')) {
    exit(1);
}
$url = $env["WEBHOOK_URL"];
$uid = $env["PLACE_ID"];
$fetch = new TenkiFetch($uid);
$res = $fetch->fetch();
if (!$res) {
    exit(1);
}

$weather = new Weather($uid, $res);
$webhook = new WebhookNHKNews($url, $weather);
$webhook->send();
