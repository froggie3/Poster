<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 0-6 時、6-12 時、12-18 時、18-24 時のそれぞれ 6 時間ごとの降水確率。
 */
class Rainy6h
{
    /**
     * 始点時刻。
     *
     * @var string $time
     */
    readonly string $time;

    /**
     * 降水確率。
     * 現在時刻より前の始点時刻の降水確率は "-9999" で表現される。
     * 
     * @var string $rain
     */
    readonly string $rain;

    public function __construct(string $time, string $rain)
    {
        $this->time = $time;
        $this->rain = $rain;
    }
}
