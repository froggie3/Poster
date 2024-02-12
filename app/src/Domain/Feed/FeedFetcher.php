<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use FeedIo\FeedIo;
use FeedIo\Reader\Result;
use Monolog\Logger;

class FeedFetcher
{
    private Logger $logger;
    private \FeedIo\FeedIo $feedIo;

    public function __construct(Logger $logger, FeedIo $feedIo)
    {
        $this->logger = $logger;
        $this->feedIo = $feedIo;

        $this->logger->info('FeedFetcher initialized');
    }

    public function fetch(string $url): Result
    {
        try {
            $this->logger->info("Sending request", ['url' => $url]);
            return $this->feedIo->read($url);
        } catch (\FeedIo\FeedIoException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error("Error parsing feed", ['url' => $url]);
        }
    }
}
