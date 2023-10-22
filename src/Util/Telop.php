<?php

namespace App\Util;

class Telop
{
    public const TelopData = [
        100 => ["晴れ", ":sunny:"],
        101 => ["晴れ時々くもり", ":partly_sunny:"],
        102 => ["晴れ一時雨", ":white_sun_rain_cloud:"],
        103 => ["晴れ時々雨", ":white_sun_rain_cloud:"],
        105 => ["晴れ一時雪", ":white_sun_rain_cloud:"],
        110 => ["晴れのちくもり", ":white_sun_cloud:"],
        111 => ["晴れのちくもり", ":white_sun_cloud:"],  # dup: 110
        113 => ["晴れのち雨", ":white_sun_rain_cloud:"],
        114 => ["晴れのち雨", ":white_sun_rain_cloud:"],  # dup: 113
        115 => ["晴れのち雪", ":white_sun_rain_cloud:"],
        200 => ["くもり", ":cloud:"],
        201 => ["くもり時々晴れ", ":partly_sunny:"],
        202 => ["くもり一時雨", ":cloud_rain:"],
        203 => ["くもり時々雨", ":cloud_rain:"],
        205 => ["くもり一時雪", ":cloud_rain:"],
        210 => ["くもりのち晴れ", ":white_sun_cloud:"],
        211 => ["くもりのち晴れ", ":white_sun_cloud:"],  # dup: 210
        213 => ["くもりのち雨", ":cloud_rain:"],
        214 => ["くもりのち雨", ":cloud_rain:"],  # dup: 213
        215 => ["くもりのち雪", ":cloud_rain:"],
        300 => ["雨", ":cloud_rain:"],
        301 => ["雨時々晴れ", ":white_sun_rain_cloud:"],
        302 => ["雨一時くもり", ":white_sun_small_cloud:"],
        303 => ["雨時々雪", ":cloud_snow:"],
        308 => ["暴風雨", ":cloud_rain:"],
        311 => ["雨のち晴れ", ":white_sun_rain_cloud:"],
        313 => ["雨のちくもり", ":cloud_rain:"],
        315 => ["雨のち雪", ":cloud_snow:"],
        400 => ["雪", ":snowflake:"],
        401 => ["雪時々晴れ", ":cloud_snow:"],
        402 => ["雪時々やむ", ":cloud_snow:"],
        403 => ["雪時々雨", ":cloud_snow:"],
        407 => ["暴風雪", ":cloud_snow:"],
        409 => ["雪時々雨", ":cloud_snow:"],
        411 => ["雪のち晴れ", ":white_sun_rain_cloud:"],
        413 => ["雪のちくもり", ":cloud_snow:"],
        414 => ["雪のち雨", ":cloud_snow:"]
    ];
}
