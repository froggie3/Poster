<?php

declare(strict_types=1);

namespace App\DataTypes;

use App\Utils\Telop;

class Weather
{
    public $locationUid;
    public $locationName;
    public $forecastDate;
    public $maxTemp;
    public $maxTempDiff;
    public $minTemp;
    public $minTempDiff;
    public $rainyDay;
    public $telop;
    public $weather;
    public $weatherEmoji;
    public $telopFile;

    public function __construct($uid, $weatherData)
    {
        $pref = $weatherData['lv2_info']['name'];
        $district = $weatherData['name'];
        $this->locationUid = $uid;
        $this->locationName = $pref . $district;

        $forecastThreeDays = $weatherData['trf']['forecast'];
        $forecastToday = $forecastThreeDays[0];

        $this->forecastDate = $weatherData['created_date'];
        $this->maxTemp = $forecastToday['max_temp'];
        $this->maxTempDiff = $forecastToday['max_temp_diff'];
        $this->minTemp = $forecastToday['min_temp'];
        $this->minTempDiff = $forecastToday['min_temp_diff'];
        $this->rainyDay = $forecastToday['rainy_day'];

        $this->telop = $forecastToday['telop'];
        [$this->weather, $this->weatherEmoji, $this->telopFile] = Telop::TelopData[$this->telop];
    }
}
