<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Trf
{
    readonly array $forecast;
    readonly TrfAtr $trfAtr;

    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new TrfForecast($item), $data['forecast']);
        $this->trfAtr = new TrfAtr($data['trf_atr']);
    }
}
