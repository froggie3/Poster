<?php

declare(strict_types=1);

namespace App\Utils;

class Telop
{
    public const TelopData = [
        100 =>
        [
            0 => '晴れ',
            1 => ':sunny:',
            2 => 'tlp100.png',
        ],
        101 =>
        [
            0 => '晴れ時々くもり',
            1 => ':partly_sunny:',
            2 => 'tlp101.png',
        ],
        102 =>
        [
            0 => '晴れ一時雨',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp102.png',
        ],
        103 =>
        [
            0 => '晴れ時々雨',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp103.png',
        ],
        105 =>
        [
            0 => '晴れ一時雪',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp105.png',
        ],
        110 =>
        [
            0 => '晴れのちくもり',
            1 => ':white_sun_cloud:',
            2 => 'tlp110.png',
        ],
        111 =>
        [
            0 => '晴れのちくもり',
            1 => ':white_sun_cloud:',
            2 => 'tlp110.png', # Note it shares the file with another
        ],
        113 =>
        [
            0 => '晴れのち雨',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp113.png',
        ],
        114 =>
        [
            0 => '晴れのち雨',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp113.png', # Note it shares the file with another
        ],
        115 =>
        [
            0 => '晴れのち雪',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp115.png',
        ],
        200 =>
        [
            0 => 'くもり',
            1 => ':cloud:',
            2 => 'tlp200.png',
        ],
        201 =>
        [
            0 => 'くもり時々晴れ',
            1 => ':partly_sunny:',
            2 => 'tlp201.png',
        ],
        202 =>
        [
            0 => 'くもり一時雨',
            1 => ':cloud_rain:',
            2 => 'tlp202.png',
        ],
        203 =>
        [
            0 => 'くもり時々雨',
            1 => ':cloud_rain:',
            2 => 'tlp203.png',
        ],
        205 =>
        [
            0 => 'くもり一時雪',
            1 => ':cloud_rain:',
            2 => 'tlp205.png',
        ],
        210 =>
        [
            0 => 'くもりのち晴れ',
            1 => ':white_sun_cloud:',
            2 => 'tlp210.png',
        ],
        211 =>
        [
            0 => 'くもりのち晴れ',
            1 => ':white_sun_cloud:',
            2 => 'tlp210.png',  # Note it shares the file with another
        ],
        213 =>
        [
            0 => 'くもりのち雨',
            1 => ':cloud_rain:',
            2 => 'tlp213.png',
        ],
        214 =>
        [
            0 => 'くもりのち雨',
            1 => ':cloud_rain:',
            2 => 'tlp213.png', # Note it shares the file with another
        ],
        215 =>
        [
            0 => 'くもりのち雪',
            1 => ':cloud_rain:',
            2 => 'tlp215.png',
        ],
        300 =>
        [
            0 => '雨',
            1 => ':cloud_rain:',
            2 => 'tlp300.png',
        ],
        301 =>
        [
            0 => '雨時々晴れ',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp301.png',
        ],
        302 =>
        [
            0 => '雨一時くもり',
            1 => ':white_sun_small_cloud:',
            2 => 'tlp302.png',
        ],
        303 =>
        [
            0 => '雨時々雪',
            1 => ':cloud_snow:',
            2 => 'tlp303.png',
        ],
        308 =>
        [
            0 => '暴風雨',
            1 => ':cloud_rain:',
            2 => 'tlp308.png',
        ],
        311 =>
        [
            0 => '雨のち晴れ',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp311.png',
        ],
        313 =>
        [
            0 => '雨のちくもり',
            1 => ':cloud_rain:',
            2 => 'tlp313.png',
        ],
        315 =>
        [
            0 => '雨のち雪',
            1 => ':cloud_snow:',
            2 => 'tlp315.png',
        ],
        400 =>
        [
            0 => '雪',
            1 => ':snowflake:',
            2 => 'tlp400.png',
        ],
        401 =>
        [
            0 => '雪時々晴れ',
            1 => ':cloud_snow:',
            2 => 'tlp401.png',
        ],
        402 =>
        [
            0 => '雪時々やむ',
            1 => ':cloud_snow:',
            2 => 'tlp402.png',
        ],
        403 =>
        [
            0 => '雪時々雨',
            1 => ':cloud_snow:',
            2 => 'tlp403.png',
        ],
        407 =>
        [
            0 => '暴風雪',
            1 => ':cloud_snow:',
            2 => 'tlp407.png',
        ],
        409 =>
        [
            0 => '雪時々雨',
            1 => ':cloud_snow:',
            2 => 'tlp409.png',
        ],
        411 =>
        [
            0 => '雪のち晴れ',
            1 => ':white_sun_rain_cloud:',
            2 => 'tlp411.png',
        ],
        413 =>
        [
            0 => '雪のちくもり',
            1 => ':cloud_snow:',
            2 => 'tlp413.png',
        ],
        414 =>
        [
            0 => '雪のち雨',
            1 => ':cloud_snow:',
            2 => 'tlp414.png',
        ],
    ];
}
