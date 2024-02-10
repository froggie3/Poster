<?php

declare(strict_types=1);

namespace App\Fetcher;

use App\Config;
use App\Constants;
use App\Data\Forecast;
use App\Data\Telop;
use App\Data\TelopImageUsed;
use App\Interface\ForecastFetcherInterface;
use App\Interface\ProcessInterface;
use App\Utils\Http;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use stdClass;

class Query
{
    public string $uid;
    public string $kind = 'web';
    public string $akey;

    private string $akey_plain = 'nhk';

    public function __construct(string $placeId)
    {
        $this->uid = $placeId;
        $this->akey = hash('md5', $this->akey_plain);
    }

    public function buildQuery()
    {
        return http_build_query($this);
    }
}

class ForcastFetcher implements ForecastFetcherInterface
{
    private Logger $logger;
    private Client $client;
    private array $placeIds = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->client = new Client([
            'timeout' => Config::CONNECTION_TIMEOUT,
            'header' => ['User-Agent' => 'Mozilla/5.0'],
        ]);
    }

    public function addQueue(string $placeId)
    {
        $this->placeIds[] = $placeId;
        $this->logger->info("UID '$placeId' enqueued");
    }

    public function fetchForecast(): array
    {
        $result = [];
        $loggingPath = __DIR__ . '/../../../logs/app.log';
        $LogHandlers = [new StreamHandler($loggingPath, Config::MONOLOG_LOG_LEVEL), new ErrorLogHandler()];

        foreach ($this->placeIds as $placeId) {
            $query = (new Query($placeId))->buildQuery();
            $request = new Request('GET', "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?$query");
            $http = new Http(new Logger(Constants::MODULE_HTTP, $LogHandlers), $this->client);
            $res = $http->sendRequest($request);

            $result[] = new ForecastProcess(json_decode($res));
        }

        $this->logger->info("Fetching finished");
        return $result;
    }
}

class ForecastProcess implements ProcessInterface
{
    public stdClass $response;
    public array $forcastTelops;

    public function __construct(stdClass $response)
    {
        $this->response = $response;
        $this->forcastTelops = [
            100 => new Telop('晴れ',           ':sunny:',                 'tlp100.png',  -1, new TelopImageUsed(true,  true),),
            101 => new Telop('晴れ時々くもり', ':partly_sunny:',          'tlp101.png',  -1, new TelopImageUsed(true,  true),),
            102 => new Telop('晴れ一時雨',     ':white_sun_rain_cloud:',  'tlp102.png',  -1, new TelopImageUsed(true,  true),),
            103 => new Telop('晴れ時々雨',     ':white_sun_rain_cloud:',  'tlp103.png',  -1, new TelopImageUsed(true,  true),),
            104 => new Telop('晴れ一時雪',     ':white_sun_rain_cloud:',  'tlp105.png',  -1, new TelopImageUsed(true,  false),),
            105 => new Telop('晴れ一時雪',     ':white_sun_rain_cloud:',  'tlp105.png', 104, new TelopImageUsed(false, true),),
            110 => new Telop('晴れのちくもり', ':white_sun_cloud:',       'tlp110.png',  -1, new TelopImageUsed(false, true),),
            111 => new Telop('晴れのちくもり', ':white_sun_cloud:',       'tlp110.png', 110, new TelopImageUsed(true,  false),),
            113 => new Telop('晴れのち雨',     ':white_sun_rain_cloud:',  'tlp113.png',  -1, new TelopImageUsed(false, true),),
            114 => new Telop('晴れのち雨',     ':white_sun_rain_cloud:',  'tlp113.png', 113, new TelopImageUsed(true,  false),),
            115 => new Telop('晴れのち雪',     ':white_sun_rain_cloud:',  'tlp115.png',  -1, new TelopImageUsed(true,  true),),
            200 => new Telop('くもり',         ':cloud:',                 'tlp200.png',  -1, new TelopImageUsed(true,  true),),
            201 => new Telop('くもり時々晴れ', ':partly_sunny:',          'tlp201.png',  -1, new TelopImageUsed(true,  true),),
            202 => new Telop('くもり一時雨',   ':cloud_rain:',            'tlp202.png',  -1, new TelopImageUsed(true,  true),),
            203 => new Telop('くもり時々雨',   ':cloud_rain:',            'tlp203.png',  -1, new TelopImageUsed(true,  true),),
            204 => new Telop('くもり一時雪',   ':cloud_rain:',            'tlp205.png',  -1, new TelopImageUsed(true,  false),),
            205 => new Telop('くもり一時雪',   ':cloud_rain:',            'tlp205.png', 204, new TelopImageUsed(false, true),),
            210 => new Telop('くもりのち晴れ', ':white_sun_cloud:',       'tlp210.png', 211, new TelopImageUsed(false, true),),
            211 => new Telop('くもりのち晴れ', ':white_sun_cloud:',       'tlp210.png',  -1, new TelopImageUsed(true,  false),),
            213 => new Telop('くもりのち雨',   ':cloud_rain:',            'tlp213.png',  -1, new TelopImageUsed(false, true),),
            214 => new Telop('くもりのち雨',   ':cloud_rain:',            'tlp213.png', 213, new TelopImageUsed(true,  false),),
            215 => new Telop('くもりのち雪',   ':cloud_rain:',            'tlp215.png',  -1, new TelopImageUsed(false, true),),
            217 => new Telop('くもりのち雪',   ':cloud_rain:',            'tlp215.png', 215, new TelopImageUsed(true,  false),),
            300 => new Telop('雨',             ':cloud_rain:',            'tlp300.png',  -1, new TelopImageUsed(true,  true),),
            301 => new Telop('雨時々晴れ',     ':white_sun_rain_cloud:',  'tlp301.png',  -1, new TelopImageUsed(true,  true),),
            302 => new Telop('雨一時くもり',   ':white_sun_small_cloud:', 'tlp302.png',  -1, new TelopImageUsed(true,  true),),
            303 => new Telop('雨時々雪',       ':cloud_snow:',            'tlp303.png',  -1, new TelopImageUsed(true,  true),),
            308 => new Telop('暴風雨',         ':cloud_rain:',            'tlp308.png',  -1, new TelopImageUsed(true,  true),),
            311 => new Telop('雨のち晴れ',     ':white_sun_rain_cloud:',  'tlp311.png',  -1, new TelopImageUsed(true,  true),),
            313 => new Telop('雨のちくもり',   ':cloud_rain:',            'tlp313.png',  -1, new TelopImageUsed(true,  true),),
            315 => new Telop('雨のち雪',       ':cloud_snow:',            'tlp315.png',  -1, new TelopImageUsed(true,  true),),
            400 => new Telop('雪',             ':snowflake:',             'tlp400.png',  -1, new TelopImageUsed(true,  true),),
            401 => new Telop('雪時々晴れ',     ':cloud_snow:',            'tlp401.png',  -1, new TelopImageUsed(true,  true),),
            402 => new Telop('雪時々やむ',     ':cloud_snow:',            'tlp402.png',  -1, new TelopImageUsed(true,  true),),
            403 => new Telop('雪時々雨',       ':cloud_snow:',            'tlp403.png',  -1, new TelopImageUsed(true,  true),),
            407 => new Telop('暴風雪',         ':cloud_snow:',            'tlp407.png',  -1, new TelopImageUsed(true,  true),),
            409 => new Telop('雪時々雨',       ':cloud_snow:',            'tlp409.png',  -1, new TelopImageUsed(true,  true),),
            411 => new Telop('雪のち晴れ',     ':white_sun_rain_cloud:',  'tlp411.png',  -1, new TelopImageUsed(true,  true),),
            413 => new Telop('雪のちくもり',   ':cloud_snow:',            'tlp413.png',  -1, new TelopImageUsed(true,  true),),
            414 => new Telop('雪のち雨',       ':cloud_snow:',            'tlp414.png',  -1, new TelopImageUsed(true,  true),),
        ];
    }

    protected function processOne(stdClass $res, Telop $tp, stdClass $fc): Forecast
    {
        $forecast = new Forecast(
            $res->uid,
            "{$res->lv2_info->name}{$res->name}",
            $res->trf->trf_atr->reported_date,
            $fc->max_temp,
            $fc->max_temp_diff,
            $fc->min_temp,
            $fc->min_temp_diff,
            $fc->rainy_day,
            $tp->distinct_name,
            $tp->emoji_name,
            $tp->telop_filename
        );

        return $forecast;
    }

    protected function processThreeDays(): array
    {
        $forecastThreeDays = [];
        foreach ($this->response->trf->forecast as $forecast) {
            if (!array_key_exists($forecast->telop, $this->forcastTelops)) {
                throw new Exception('Unsupported telop');
            }
            $forecastThreeDays[] = self::processOne(
                $this->response,
                $this->forcastTelops[$forecast->telop],
                $forecast
            );
        }

        return $forecastThreeDays;
    }

    public function process(): array
    {
        return $this->processThreeDays();
    }
}
