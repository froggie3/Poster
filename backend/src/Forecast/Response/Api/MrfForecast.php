<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class MrfForecast
{
    readonly string $maxTemp;
    readonly string $telop;
    readonly string $holiday;
    readonly string $forecastDate;
    readonly string $minTemp;
    readonly string $pop;

    public function __construct(array $data)
    {
        $this->maxTemp = $data['max_temp'];
        $this->telop = $data['telop'];
        $this->holiday = $data['holiday'];
        $this->forecastDate = $data['forecast_date'];
        $this->minTemp = $data['min_temp'];
        $this->pop = $data['pop'];
    }
}
