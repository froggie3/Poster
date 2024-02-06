<?php

declare(strict_types=1);

namespace App\FeedExtractor;

use \App\FeedExtractor\FeedExtractor;
use DateTimeImmutable;
use SimpleXMLElement;


/**
 * Extract the all latest article records based on given RSS feed data.
 *
 * returns false if it fails.
 */
class RSSFeedExtractor extends FeedExtractor
{
    public array $articles;

    public function __construct(SimpleXMLElement $xml)
    {
        $this->articles = [];
        foreach ($xml->xpath("//channel/item") as $node) {
            $this->articles[] = $this->processItem($node);
        }
    }

    private function processItem(SimpleXMLElement $node): array
    {
        $entry = [];
        foreach (["title", "link", "pubDate"] as $key) {
            // 時刻をオブジェクトに変換しておく
            if ($key == "pubDate") {
                $entry[$key] = DateTimeImmutable::createFromFormat(
                    DateTimeImmutable::RSS,
                    (string)$node->$key
                );
                continue;
            }
            $entry[$key] = (string)$node->$key;
        }
        return $entry;
    }

    /**
     * returns entries, or false if it fails.
     */
    public function getEntries(): array | false
    {
        if ($this->articles) {
            return $this->articles;
        }
        return false;
    }
}
