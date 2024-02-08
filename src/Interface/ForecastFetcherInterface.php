<?php

declare(strict_types=1);

namespace App\Interface;

interface ForecastFetcherInterface
{
    public function fetchForecast(): void;
}
