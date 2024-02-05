<?php

declare(strict_types=1);

namespace App\XML;

use \App\Request\RSSRequest;
use SimpleXMLElement;

class FeedParser
{
    function __construct()
    {
    }

    /**
     * Tries to save the retrieved latest articles into an existing SQLite database.
     * If the database has existing article, tries to save only difference set. 
     *
     * returns false if it fails.
     */
    function saveLatestRss(array $articles): bool
    {
        if (1) {
            print_r($articles);
            return true;
        }
        return false;
    }

}
