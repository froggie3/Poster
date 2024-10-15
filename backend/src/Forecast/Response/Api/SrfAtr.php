<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class SrfAtr
{
    readonly string $reportedDate;

    public function __construct(array $data)
    {
        $this->reportedDate = $data['reported_date'];
    }
}
