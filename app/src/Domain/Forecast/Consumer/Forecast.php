<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Consumer;

use App\Data\Discord\Author;
use App\Data\Discord\DiscordPost;
use App\Data\Discord\Embed;
use App\Data\Discord\Field;
use App\Data\Discord\Footer;
use App\Data\Discord\Thumbnail;

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
        string $telopFile
    ) {
        $this->locationUid  = $locationUid;
        $this->locationName = $locationName;
        $this->forecastDate = $forecastDate;
        $this->maxTemp      = $maxTemp;
        $this->maxTempDiff  = $maxTempDiff;
        $this->minTemp      = $minTemp;
        $this->minTempDiff  = $minTempDiff;
        $this->rainyDay     = $rainyDay;
        $this->weather      = $weather;
        $this->weatherEmoji = $weatherEmoji;
        $this->telopFile    = $telopFile;
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
                (new \DateTimeImmutable($this->forecastDate))->format("H:i")
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
                    "thumbnail" => new Thumbnail(["url" => $this->thumbnailUrl,]),
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
                            "value" => $this->weatherEmoji . " " . $this->weather,
                            "inline" => false,
                        ]),
                        new Field([
                            "name" => "最高気温",
                            "value" => sprintf(":chart_with_upwards_trend: %d ℃ (%+d ℃)", $this->maxTemp, $this->maxTempDiff),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "最低気温",
                            "value" => sprintf(":chart_with_downwards_trend: %d ℃ (%+d ℃)", $this->minTemp, $this->minTempDiff),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "降水確率",
                            "value" => sprintf(":umbrella: %d %%", $this->rainyDay),
                            "inline" => true,
                        ]),
                    ],
                ]),
            ],
        ]);
    }
}
