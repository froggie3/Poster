#!/usr/bin/env php
<?php

namespace App\FeedExtractor;

use \App\FeedExtractor\FeedExtractor;

class AtomFeedExtractor extends FeedExtractor
{
    function __construct()
    {
    }

    public function extract(object $XMLElement)
    {
        $data = $XMLElement->channel->item;
        $array = array();

        foreach ($data as $item) {
            $array[] = $item;
        }

        return $array;
    }
};
