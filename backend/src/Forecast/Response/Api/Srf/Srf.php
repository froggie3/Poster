<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Srf;

/**
 * 1 時間ごとの予報。
 */
class Srf
{
    /**
     * 1 時間ごとの天気情報を格納する配列。
     * 
     * @var Forecast[]
     */
    readonly array $forecast;

    /**
     * 情報。
     */
    readonly SrfAtr $srfAtr;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->forecast = array_map(fn($item) => new Forecast($item), $data['forecast']);
        $this->srfAtr = new SrfAtr($data['srf_atr']);
    }
}
