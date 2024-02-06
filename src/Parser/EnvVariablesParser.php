<?php

namespace App\Parser;

class EnvVariablesParser extends Parser
{
    function __construct()
    {
    }

    static public function parse(string $parameter): array
    {
        $configArray = [];
        if (!$parameter) return $configArray;
        foreach (\explode(",", $parameter) as $v) {
            $configArray[] = rtrim($v);
        }
        return $configArray;
    }
}

