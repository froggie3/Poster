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
     * Make an HTTP request to specified url and retrives the latest RSS feed in text. 
     *
     * returns false if it fails.
     */

    function captureLatestFeedData(string $url): string | false
    {
        $request = new RSSRequest($url);
        $response = $request->fetch();
        try {
            if (!$response) {
                echo "No response received", "\n";
            }
        } catch (\Exception $e) {
            return false;
        }
        return $response;
    }

    public function exportXml($filename, $data): void
    {
        $fp = fopen($filename, 'w');
        if (fwrite($fp, $data)) {
            echo 'success';
        }
    }

    public function parseXml(string $data): SimpleXMLElement
    {
        $data = new \SimpleXMLElement($data);
        if ($data !== false) {
            return $data;
        }
        throw new ('Failed to load XML Document');
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
