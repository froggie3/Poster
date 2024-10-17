<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 花粉情報のクラス
 */
class Kafun
{
    /**
     * 緯度
     */
    readonly int $lat;

    /**
     * URL
     */
    readonly string $url;

    /**
     * 経度
     */
    readonly int $lon;

    /**
     * コンストラクタ
     */
    public function __construct(array $data)
    {
        $this->lat = $data['lat'];
        $this->url = $data['url'];
        $this->lon = $data['lon'];
    }
}
