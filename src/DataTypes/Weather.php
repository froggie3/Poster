<?php

declare(strict_types=1);

namespace App\DataTypes;

/**
 * Data class that contains the weather for one day
 */
class Weather
{
    public string $locationUid;
    public string $locationName;
    public string $forecastDate;
    public string $maxTemp;
    public string $maxTempDiff;
    public string $minTemp;
    public string $minTempDiff;
    public string $rainyDay;
    public string $telop;
    public string $weather;
    public string $weatherEmoji;
    public string $telopFile;
}
