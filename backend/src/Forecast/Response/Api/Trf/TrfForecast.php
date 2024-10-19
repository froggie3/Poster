<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Trf;

/**
 * 3 日間の天気予報データの 1 日分。
 */
class TrfForecast
{
    /**
     * 予報日時。
     */
    readonly string $forecastDate;

    /**
     * 最高気温の前日差。
     */
    readonly string $maxTempDiff;

    /**
     * 最高気温。
     */
    readonly string $maxTemp;

    /**
     * テロップ番号。
     */
    readonly string $telop;

    /**
     * 最低気温の前日差。
     */
    readonly string $minTempDiff;

    /**
     * 最低気温。
     */
    readonly string $minTemp;

    /**
     * 6 時間ごとの降水確率。
     *
     * @var Rainy6h[] $rainy6h
     */
    readonly array $rainy6h;

    /**
     * 一日を通じた降水確率。
     */
    readonly string $rainyDay;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->forecastDate = $data['forecast_date'];
        $this->maxTempDiff = $data['max_temp_diff'];
        $this->maxTemp = $data['max_temp'];
        $this->telop = $data['telop'];
        $this->minTempDiff = $data['min_temp_diff'];
        $this->minTemp = $data['min_temp'];
        $this->rainy6h = array_map(
            fn($time, $rain) => new Rainy6h($time, $rain),
            array_map(
                fn($item) => explode($item, "_")[0],  // e.g. "6_12" => ["6", "_", "12"]
                array_keys($data['rainy_6h'])
            ),
            array_values($data['rainy_6h'])
        );
        $this->rainyDay = $data['rainy_day'];
    }
}
