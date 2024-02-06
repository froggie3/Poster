<?php

namespace App\Parser;

use SimpleXMLElement;

class XMLParser extends Parser 
{
    function __construct()
    {
    }

    static public function parse(string $data): SimpleXMLElement
    {
        $res = new SimpleXMLElement($data);
        return $res;
        // throw new ('Failed to load XML Document');
    }
}

