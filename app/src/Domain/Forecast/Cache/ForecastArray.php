<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Cache;

class ForecastArray extends \ArrayObject
{
    public function needsUpdate(): bool
    {
        return !empty($this);
    }
}
