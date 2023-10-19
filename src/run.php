#!/usr/bin/env php
<?php
class Telop {
    public const TELOP = [
        100 => ["晴れ", ":sunny:"],
        101 => ["晴れ時々くもり", ":partly_sunny:"],
        102 => ["晴れ一時雨", ":white_sun_rain_cloud:"],
        103 => ["晴れ時々雨", ":white_sun_rain_cloud:"],
        111 => ["晴れのちくもり", ":white_sun_cloud:"],
        114 => ["晴れのち雨", ":white_sun_rain_cloud:"],
        200 => ["くもり", ":cloud:"],
        201 => ["くもり時々晴れ", ":partly_sunny:"],
        202 => ["くもり一時雨", ":cloud_rain:"],
        203 => ["くもり時々雨", ":cloud_rain:"],
        211 => ["くもりのち晴れ", ":white_sun_cloud:"],
        214 => ["くもりのち雨", ":cloud_rain:"],
        300 => ["雨", ":cloud_rain:"],
        301 => ["雨時々晴れ", ":white_sun_rain_cloud:"],
        302 => ["雨一時くもり", ":white_sun_small_cloud:"],
        303 => ["雨時々雪", ":cloud_snow:"],
        311 => ["雨のち晴れ", ":white_sun_rain_cloud:"],
        313 => ["雨のちくもり", ":cloud_rain:"],
        315 => ["雨のち雪", ":cloud_snow:"],
        400 => ["雪", ":snowflake:"],
        401 => ["雪時々晴れ", ":cloud_snow:"],
        402 => ["雪時々やむ", ":cloud_snow:"],
        403 => ["雪時々雨", ":cloud_snow:"],
        411 => ["雪のち晴れ", ":white_sun_rain_cloud:"],
        413 => ["雪のちくもり", ":cloud_snow:"],
        414 => ["雪のち雨", ":cloud_snow:"]
    ];
}

class TenkiFetch {
    private $query;
    private $weather_data;

    public function __construct($uid) {
        $this->query = http_build_query([
            'uid' => $uid,
            'kind' => "web",
            'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
        ]);
        $this->weather_data = [];
    }

    public function fetch() {
        $url = "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?" . $this->query;
        $response = file_get_contents($url);
        if ($response) {
            $this->weather_data = json_decode($response, true);
            return $this->weather_data;
        }
        return [];
    }
}

class WebhookPosting {
    protected const HEADERS = [
        "Content-Type: application/json",
        "User-Agent: Mozilla/5.0"
    ];
    protected $webhook_url;

    public function __construct() {
    }

    protected function post($url, $payload, $headers) {
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

class WebhookNHKNews extends WebhookPosting {
    private const AUTHOR_URL = "https://yokkin.com/d/forecast_resource/author.jpg";
    private const AVATAR_URL = "https://yokkin.com/d/forecast_resource/avatar.png";
    private $content;
    private $weather;
    private $location_url;
    private $thumbnail_url;
    private $timestamp;

    public function __construct($url, $weather) {
        $this->webhook_url = $url;
        $this->content = "天気でーす";
        $this->weather = $weather;
    }

    public function send() {
        $data = $this->prepare_payload($this->weather);
        $payload = json_encode($data);
        $response = $this->post($this->webhook_url, $payload, self::HEADERS);
        if ($response) {
            return true;
        }
        return false;
    }

    protected function prepare_payload($weather) {
        $this->location_url = "https://www.nhk.or.jp/kishou-saigai/city/weather/" . $weather->location_uid;
        $this->thumbnail_url = "https://yokkin.com/d/forecast_resource/tlp" . $weather->telop . ".png";
        $this->timestamp = $weather->forecast_date;

        $payload = [
            "content" => $this->content,
            "username" => "NHK NEWS WEB",
            "avatar_url" => self::AVATAR_URL,
            "embeds" => [
                [
                    "title" => "きょうの天気予報",
                    "description" => $weather->location_name . "の天気予報です",
                    "url" => $this->location_url,
                    "timestamp" => $this->timestamp,
                    "color" => 0x0076d1,
                    "image" => ["url" => "https://www3.nhk.or.jp/weather/tenki/tenki_01.jpg"],
                    "thumbnail" => ["url" => $this->thumbnail_url],
                    "footer" => [
                        "text" => "Deployed by Yokkin",
                        "icon_url" => self::AUTHOR_URL,
                    ],
                    "author" => [
                        "name" => "NHK NEWS WEB",
                        "url" => "https://www3.nhk.or.jp/news/",
                        "icon_url" => self::AVATAR_URL,
                    ],
                    "fields" => [
                        [
                            "name" => "天気",
                            "value" => $weather->weather_emoji . " " . $weather->weather,
                            "inline" => false,
                        ],
                        [
                            "name" => "最高気温",
                            "value" => ":chart_with_upwards_trend: " . $weather->max_temp . " ℃ " . "(" . $weather->max_temp_diff . " ℃)",
                            "inline" => true,
                        ],
                        [
                            "name" => "最低気温",
                            "value" => ":chart_with_downwards_trend: " . $weather->min_temp . " ℃ " . "(" . $weather->min_temp_diff . " ℃)",
                            "inline" => true,
                        ],
                        [
                            "name" => "降水確率",
                            "value" => ":umbrella: " . $weather->rainy_day . " %",
                            "inline" => true,
                        ],
                    ],
                ],
            ],
        ];

        return $payload;
    }
}

class Weather {
    public $location_name;
    public $forecast_date;
    public $max_temp;
    public $max_temp_diff;
    public $min_temp;
    public $min_temp_diff;
    public $rainy_day;
    public $telop;
    public $weather;
    public $weather_emoji;

    public function __construct($weather_data) {
        $pref = $weather_data['lv2_info']['name'];
        $district = $weather_data['name'];
        $this->location_uid = $weather_data['uid'];
        $this->location_name = $pref . $district;

        $forecast_three_days = $weather_data['trf']['forecast'];
        $forecast_today = $forecast_three_days[0];

        $this->forecast_date = $forecast_today['forecast_date'];
        $this->max_temp = $forecast_today['max_temp'];
        $this->max_temp_diff = $forecast_today['max_temp_diff'];
        $this->min_temp = $forecast_today['min_temp'];
        $this->min_temp_diff = $forecast_today['min_temp_diff'];
        $this->rainy_day = $forecast_today['rainy_day'];

        $this->telop = $forecast_today['telop'];
        [$this->weather, $this->weather_emoji] = Telop::TELOP[$this->telop];
    }
}

$env = parse_ini_file('.env');
$url = $env["WEBHOOK_URL"];

$fetch = new TenkiFetch($env["PLACE_ID"]);
$res = $fetch->fetch();
if (!$res) {
    exit(1);
}

$weather = new Weather($res);
$webhook = new WebhookNHKNews($url, $weather);
$webhook->send();

