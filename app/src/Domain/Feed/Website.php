<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use FeedIo\Feed\Item;
use FeedIo\FeedInterface;
use Monolog\Logger;
use FeedIo\FeedIo;

class Website
{
    private FeedIo $feedIo;
    private Logger $logger;
    private array $articles;
    private int $id;
    private string $url;

    public function __construct(FeedIo $feedIo, int $id, string $url, Logger $logger = null)
    {
        $this->feedIo = $feedIo;
        $this->id = $id;
        $this->url = $url;

        if (!is_null($logger)) {
            $this->logger = $logger;
        }
    }

    public function process(): self
    {
        $this->request();
        return $this;
    }

    private function request(): void
    {
        try {
            $result = $this->feedIo->read($this->url);
        } catch (\FeedIo\FeedIoException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error("Error parsing feed", ['url' => $this->url]);
        }

        foreach ($result->getFeed() as $item) {
            $this->articles[] = new Article($item->getTitle(), $item->getLink(), $item->getLastModified());
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }

    public function countArticles(): int
    {
        return count($this->articles);
    }
}
