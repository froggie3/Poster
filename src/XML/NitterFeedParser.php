#!/usr/bin/env php
<?php

namespace App\XML;

use \App\XML\FeedParser;

class NitterFeedParser extends FeedParser
{
    function __construct()
    {
    }

    public function extractLatestRss(object $XMLElement)
    {
        $data = $XMLElement->channel->item;
        $array = array();

        foreach ($data as $item) {
            $array[] = $item;
        }

        return $array;
    }
};
