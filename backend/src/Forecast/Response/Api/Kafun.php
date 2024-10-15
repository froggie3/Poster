<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Kafun
{
    readonly int $lat;
    readonly string $url;
    readonly int $lon;

    public function __construct(array $data)
    {
        $this->lat = $data['lat'];
        $this->url = $data['url'];
        $this->lon = $data['lon'];
    }
}
