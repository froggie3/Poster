<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\Article;
use FeedIo\Feed\Item;
use FeedIo\Reader\Result;

/** 必要な情報を抽出するための処理群 */
class FeedFilter
{
    private Result $result;
    private array $articles = [];

    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /** Returns the processed articles */
    public function get(): array { return $this->articles; }

    /** Process Result object */
    public function process(): void 
    {
        foreach ($this->result->getFeed() as $item) {
            $this->articles[] = new Article($item->getTitle(), $item->getLink(), $item->getLastModified());
        }
    }
}
