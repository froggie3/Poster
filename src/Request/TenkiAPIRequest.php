<?php

namespace App\Request;

class TenkiAPIRequest extends Request
{
    private $query;
    private const API_ENDPOINT = "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?";

    public function __construct($uid)
    {
        $this->query = [
            'uid' => $uid,
            'kind' => "web",
            'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
        ];
        parent::__construct();
    }

    public function fetch()
    {
        $response = $this->get(self::API_ENDPOINT, $this->query);
        try {
            $weatherData = json_decode($response, true, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            echo $exception->getMessage() . "\n"; // displays "Syntax error"  
            return [];
        }
        return $weatherData;
    }
}
