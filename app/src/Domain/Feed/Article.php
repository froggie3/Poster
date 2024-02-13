<?php

declare(strict_types=1);

namespace App\Domain\Feed;

class Article
{
    public string $title;
    public string $link;
    public \DateTime $updatedAt;

    public function __construct(string $title, string $link, \DateTime $updatedAt,)
    {
        $this->title = $title;
        $this->link = $link;
        $this->updatedAt = $updatedAt;
    }
}
