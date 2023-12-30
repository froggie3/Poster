#!/usr/bin/env php
<?php

namespace App\XML;

use \App\XML\FeedParser;
use SimpleXMLElement;

class WordPressFeedParser extends FeedParser
{

    /**
     * Extract the all latest article records based on given RSS feed data.
     *
     * returns false if it fails.
     */
    function extractLatestRss(SimpleXMLElement $feedConverted): array | false
    {
        $articles = [];
        $searchKeys = ["title", "link", "pubDate"];
        foreach ($feedConverted->xpath("//channel/item") as $node) {
            $tmp = [];
            foreach ($searchKeys as $key) {
                $tmp[$key] = (string)$node[0]->{"$key"};
            }
            $articles[] = $tmp;
        }
        #print_r([$titles, $links, $publishedDates]);
        return $articles;
    }
}
