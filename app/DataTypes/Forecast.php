<?php

declare(strict_types=1);

namespace App\DataTypes;

/**
 * Data class that contains the weather for one day
 */
class Forecast
{
    public string $locationUid;
    public string $locationName; // foo県bar市baz区
    public string $forecastDate;
    public string $maxTemp;
    public string $maxTempDiff;
    public string $minTemp;
    public string $minTempDiff;
    public string $rainyDay;
    public string $weather;
    public string $weatherEmoji;
    public string $telopFile;

    public function __construct(
        string $locationUid,
        string $locationName,
        string $forecastDate,
        string $maxTemp,
        string $maxTempDiff,
        string $minTemp,
        string $minTempDiff,
        string $rainyDay,
        string $weather,
        string $weatherEmoji,
        string $telopFile
    ) {
        $this->locationUid  = $locationUid;
        $this->locationName = $locationName;
        $this->forecastDate = $forecastDate;
        $this->maxTemp      = $maxTemp;
        $this->maxTempDiff  = $maxTempDiff;
        $this->minTemp      = $minTemp;
        $this->minTempDiff  = $minTempDiff;
        $this->rainyDay     = $rainyDay;
        $this->weather      = $weather;
        $this->weatherEmoji = $weatherEmoji;
        $this->telopFile    = $telopFile;
    }
}
