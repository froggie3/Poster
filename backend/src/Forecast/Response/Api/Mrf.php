<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Mrf
{
    readonly array $forecast;
    readonly MrfAtr $mrfAtr;

    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new MrfForecast($item), $data['forecast']);
        $this->mrfAtr = new MrfAtr($data['mrf_atr']);
    }
}
