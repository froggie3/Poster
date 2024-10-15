<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Srf
{
    readonly array $forecast;
    readonly SrfAtr $srfAtr;

    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new Forecast($item), $data['forecast']);
        $this->srfAtr = new SrfAtr($data['srf_atr']);
    }
}
