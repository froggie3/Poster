<?php

declare(strict_types=1);

namespace App\Request;

class WebhookNHKNewsRequest extends Request
{
    private const AuthorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
    private const AvatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
    private $content;
    private $weather;
    private $locationUrl;
    private $thumbnailUrl;

    public function __construct($url, $weather)
    {
        parent::__construct();
        $this->url = $url;
        $this->headers[] = "Content-Type: application/json";
        $this->weather = $weather;
    }

    public function send(): string | false
    {
        $data = $this->preparePayload($this->weather);
        $payload = json_encode($data);
        return $this->post($this->url, $payload);
    }

    protected function preparePayload($weather): array
    {
        $this->locationUrl = "https://www.nhk.or.jp/kishou-saigai/city/weather/" . $weather->locationUid;
        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/" . $weather->telopFile;

        $date = (new \DateTimeImmutable($weather->forecastDate))->format("H:i");
        $this->content = "天気でーす（データは $date 時点）";

        // to be separated into dedicated class maybe?
        $payload = [
            "content" => $this->content,
            "username" => "NHK NEWS WEB",
            "avatar_url" => self::AvatarUrl,
            "embeds" => [
                [
                    "title" => "きょうの天気予報",
                    "description" => $weather->locationName . "の天気予報です",
                    "url" => $this->locationUrl,
                    "timestamp" => $weather->forecastDate,
                    "color" => 0x0076d1,
                    // "image" => ["url" => "https://www3.nhk.or.jp/weather/tenki/tenki_01.jpg"],
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
                            "value" => sprintf(":chart_with_upwards_trend: %d ℃ (%+d ℃)", $weather->maxTemp, $weather->maxTempDiff),
                            "inline" => true,
                        ],
                        [
                            "name" => "最低気温",
                            "value" => sprintf(":chart_with_downwards_trend: %d ℃ (%+d ℃)", $weather->minTemp, $weather->minTempDiff),
                            "inline" => true,
                        ],
                        [
                            "name" => "降水確率",
                            "value" => sprintf(":umbrella: %d %%", $weather->rainyDay),
                            "inline" => true,
                        ],
                    ],
                ],
            ],
        ];

        return $payload;
    }
}