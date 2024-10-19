<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Mrf;

/**
 * 10 日間天気予報のクラス。
 */
class Mrf
{
    /**
     * 日ごとのデータを格納する配列。
     * 
     * @var MrfForecast[] $forecast
     */
    readonly array $forecast;

    /**
     * 10 日間天気予報の情報。
     */
    readonly MrfAtr $mrfAtr;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new MrfForecast($item), $data['forecast']);
        $this->mrfAtr = new MrfAtr($data['mrf_atr']);
    }
}
