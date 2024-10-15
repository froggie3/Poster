<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Forecast
{
    readonly string $windDir;
    readonly string $rain;
    readonly string $telop;
    readonly string $forecastDate;
    readonly string $temp;
    readonly string $yesterdayTemp;
    readonly string $windVel;

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
