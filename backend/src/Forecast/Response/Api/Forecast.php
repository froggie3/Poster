<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 1 時間単位の天気情報。
 */
class Forecast
{
    /**
     * 風の向き。
     */
    readonly string $windDir;

    /**
     * 降水量。
     */
    readonly string $rain;

    /**
     * テロップ。
     */
    readonly string $telop;

    /**
     * 予報日時。
     */
    readonly string $forecastDate;

    /**
     * 気温。
     */
    readonly string $temp;

    /**
     * 気温の前日比。
     */
    readonly string $yesterdayTemp;

    /**
     * 風速。
     */
    readonly string $windVel;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->windDir = $data['winddir'];
        $this->rain = $data['rain'];
        $this->telop = $data['telop'];
        $this->forecastDate = $data['forecast_date'];
        $this->temp = $data['temp'];
        $this->yesterdayTemp = $data['yesterday_temp'];
        $this->windVel = $data['windvel'];
    }
}
