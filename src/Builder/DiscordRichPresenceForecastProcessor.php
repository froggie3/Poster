<?php

declare(strict_types=1);

namespace App\Builder;

use App\DataTypes\Forecast;
use App\Interface\DiscordRichPresenceBuilderInterface;
use App\DataTypes\Discord\{Author, Card, Embed, Field, Footer, Thumbnail};


class DiscordRichPresenceForecastProcessor extends Forecast implements DiscordRichPresenceBuilderInterface
{
    public const AuthorUrl = "https://yokkin.com/d/forecast_resource/author.jpg";
    public const AvatarUrl = "https://yokkin.com/d/forecast_resource/avatar.png";
    public string $thumbnailUrl;
    public string $locationUrl;

    public function __construct(Forecast $data)
    {
        // データクラスから値を自身にコピーする
        foreach ($data as $key => $var) {
            $this->{$key} = $var;
        }

        $this->thumbnailUrl = "https://yokkin.com/d/forecast_resource/{$this->telopFile}";
        $this->locationUrl = "https://www.nhk.or.jp/kishou-saigai/city/weather/{$this->locationUid}";
    }

    public function preparePayload(): Card
    {
        return new Card([
            "content" => sprintf(
                "天気でーす（データは %s 時点）",
                (new \DateTimeImmutable($this->forecastDate))->format("H:i")
            ),
            "username" => "NHK NEWS WEB",
            "avatar_url" => self::AvatarUrl,
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
                        "icon_url" => self::AuthorUrl,
                    ]),
                    "author" => new Author([
                        "name" => "NHK NEWS WEB",
                        "url" => "https://www3.nhk.or.jp/news/",
                        "icon_url" => self::AvatarUrl,
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
