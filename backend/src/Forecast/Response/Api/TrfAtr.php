<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

/**
 * 3 日間の天気予報を表すクラスの属性
 */
class TrfAtr
{
    /**
     * 報告日時
     * 
     * @var \DateTimeImmutable $reportedDate
     */
    readonly \DateTimeImmutable $reportedDate;

    public function __construct(array $data)
    {
        $this->reportedDate = new \DateTimeImmutable($data['reported_date']);
    }
}
