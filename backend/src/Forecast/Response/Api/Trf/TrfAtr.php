<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Trf;

/**
 * 3 日間の天気予報を表すクラスの属性。
 */
class TrfAtr
{
    /**
     * 予報日時。
     */
    readonly \DateTimeImmutable $reportedDate;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->reportedDate = new \DateTimeImmutable($data['reported_date']);
    }
}
