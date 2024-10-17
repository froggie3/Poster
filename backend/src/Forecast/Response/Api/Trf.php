<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 3 日間の天気予報を表すクラス。
 */
class Trf
{
    /**
     * 3 日間の天気予報データを格納する配列
     * 
     * @var TrfForecast[] $forecast
     */
    readonly array $forecast;

    /**
     * 属性
     * 
     * @var TrfAtr $trfAtr
     */
    readonly TrfAtr $trfAtr;

    /**
     * コンストラクタ
     */
    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new TrfForecast($item), $data['forecast']);
        $this->trfAtr = new TrfAtr($data['trf_atr']);
    }
}
