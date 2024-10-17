<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 10 日間天気予報の情報。
 */
class MrfAtr
{
    /**
     * 予報日時。
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
