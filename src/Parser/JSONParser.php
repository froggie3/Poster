<?php

namespace App\Parser;

class JSONParser extends Parser
{
    function __construct()
    {
    }

    static public function parse(string $data): array
    {
        //$res = \json_decode($data, true, JSON_THROW_ON_ERROR);
        $res = \json_decode($data, true);
        return $res;
    }
}

