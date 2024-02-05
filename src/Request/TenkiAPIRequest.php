<?php

declare(strict_types=1);

namespace App\Request;

class TenkiAPIRequest extends Request
{
    private array $queries;
    private const API_ENDPOINT = "https://www.nhk.or.jp/weather-data/v1/lv3/wx/?";

    public function __construct($uid)
    {
        $this->queries = [
            'uid' => $uid,
            'kind' => "web",
            'akey' => "18cce8ec1fb2982a4e11dd6b1b3efa36"  // MD5 checksum of "nhk"
        ];
        parent::__construct();
    }

    public function fetch(): string 
    {
        $response = $this->get(self::API_ENDPOINT, $this->queries);
        return $response;
    }
}
