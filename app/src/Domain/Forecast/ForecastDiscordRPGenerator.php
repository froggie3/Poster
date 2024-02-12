<?php

declare(strict_types=1);

namespace App\Domain\Forecast;

use App\Data\Discord\Author;
use App\Data\Discord\Card;
use App\Data\Discord\Embed;
use App\Data\Discord\Field;
use App\Data\Discord\Footer;
use App\Data\Discord\Thumbnail;
use App\Data\Forecast;
use App\Interface\DiscordRPGeneratorInterface;
use Monolog\Logger;

class ForecastDiscordRPGenerator extends Forecast implements DiscordRPGeneratorInterface
{
    public string $authorUrl;
    public string $avatarUrl;
    public string $thumbnailUrl;
    public string $locationUrl;
    private Logger $logger;

    public function __construct(Logger $logger, Forecast $data)
    {
        $this->logger = $logger;
        // データクラスから値を自身にコピーする
        foreach ($data as $key => $var) {
            $this->{$key} = $var;
        }

        $this->authorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
        $this->avatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/{$this->telopFile}";
        $this->locationUrl = "https://www.nhk.or.jp/kishou-saigai/city/weather/{$this->locationUid}";

        $this->logger->debug("Rich presence generation initialized");
    }

    public function process(): Card
    {
        return new Card([
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
                    "url" => $this->locationUrl,
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
