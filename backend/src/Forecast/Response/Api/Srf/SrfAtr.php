<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Srf;

/**
 * 1 時間ごとの天気の情報。
 */
class SrfAtr
{
    /**
     * 10 日間天気予報の情報。
     */
    readonly string $reportedDate;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->reportedDate = $data['reported_date'];
    }
}
