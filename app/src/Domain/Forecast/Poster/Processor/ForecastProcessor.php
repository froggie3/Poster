<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Poster\Processor;

use App\Domain\Forecast\Poster\Processor\Telop\Telop;
use App\Domain\Forecast\Poster\Processor\Telop\TelopImageUsed;
use App\Data\Discord\Author;
use App\Data\Discord\DiscordPost;
use App\Data\Discord\Embed;
use App\Data\Discord\Field;
use App\Data\Discord\Footer;
use App\Data\Discord\Thumbnail;
use App\Domain\Forecast\Consumer\ForecastDto;
use App\Domain\Forecast\Poster\Processor\Forecast;
use Monolog\Logger;


class ForecastProcessor
{
    private Forecast $forecast;
    public \stdClass $response;
    public array $ForecastTelops;
    private Logger $logger;

    public string $authorUrl;
    public string $avatarUrl;
    public string $thumbnailUrl;
    public string $locationUrl;

    public function __construct()
    {
    }

    public function process(Forecast $forecast): DiscordPost
    {
        $this->authorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
        $this->avatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/{$forecast->telopFile}";
        $this->locationUrl = "https://www.nhk.or.jp/kishou-saigai/city/weather/{$forecast->locationUid}";

        return new DiscordPost([
            "content" => sprintf(
                "天気でーす（データは %s 時点）",
                (new \DateTimeImmutable($forecast->forecastDate))->format("H:i")
            ),
            "username" => "NHK NEWS WEB",
            "avatar_url" => $this->avatarUrl,
            "embeds" => [
                new Embed([
                    "title" => "きょうの天気予報",
                    "description" => "{$forecast->locationName}の天気予報です",
                    "url" => $this->locationUrl,
                    "timestamp" => $forecast->forecastDate,
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
                            "value" => $forecast->weatherEmoji . " " . $forecast->weather,
                            "inline" => false,
                        ]),
                        new Field([
                            "name" => "最高気温",
                            "value" => sprintf(":chart_with_upwards_trend: %d ℃ (%+d ℃)", $forecast->maxTemp, $forecast->maxTempDiff),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "最低気温",
                            "value" => sprintf(":chart_with_downwards_trend: %d ℃ (%+d ℃)", $forecast->minTemp, $forecast->minTempDiff),
                            "inline" => true,
                        ]),
                        new Field([
                            "name" => "降水確率",
                            "value" => sprintf(":umbrella: %d %%", $forecast->rainyDay),
                            "inline" => true,
                        ]),
                    ],
                ]),
            ],
        ]);
    }
}
