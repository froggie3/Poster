<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class TrfForecast
{
    readonly string $forecastDate;
    readonly string $maxTempDiff;
    readonly string $maxTemp;
    readonly string $telop;
    readonly string $minTempDiff;
    readonly string $minTemp;

    // あとでオブジェクトにしておく
    readonly array $rainy6h;
    readonly string $rainyDay;

    public function __construct(array $data)
    {
        $this->forecastDate = $data['forecast_date'];
        $this->maxTempDiff = $data['max_temp_diff'];
        $this->maxTemp = $data['max_temp'];
        $this->telop = $data['telop'];
        $this->minTempDiff = $data['min_temp_diff'];
        $this->minTemp = $data['min_temp'];
        $this->rainy6h = $data['rainy_6h'];
        $this->rainyDay = $data['rainy_day'];
    }
}
