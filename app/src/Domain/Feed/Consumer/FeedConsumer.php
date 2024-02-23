<?php

declare(strict_types=1);

namespace App\Domain\Feed\Consumer;

use App\Domain\Feed\Cache\PostsArray;
use App\Domain\Feed\Cache\PostDto;
use App\Utils\DiscordPostPoster;
use App\Utils\ClientFactory;
use Monolog\Logger;

class FeedConsumer
{
    private \PDO $db;
    private array $posts;
    private Logger $logger;

    public function __construct(
        Logger $logger,
        \PDO $pdo,
        PostsArray $queue
    ) {
        $this->logger = $logger;
        $this->posts = (array)$queue;
        $this->db = $pdo;
    }

    public function post(): void
    {
        $this->logger->info("Processing queue", ['in queue' => count($this->posts)]);

        while ($p = array_shift($this->posts)) {
            $content = "$p->articleTitle\n$p->articleUrl";
            $builder = new FeedDiscordRPGenerator($content);

            $card = $builder->process();

            $cp = new DiscordPostPoster(
                $this->logger,
                (new ClientFactory($this->logger, [
                    'Content-Type' => 'application/json'
                ]))->create(),
                $card,
                $p->webhookUrl
            );

            $cp->post();
            $this->addHistory($p);

            $this->logger->info("Message sent", ['message' => $content, 'in queue' => count($this->posts)]);

            if (!empty($this->posts)) {
                $this->logger->debug(
                    "Waiting for the next request",
                    ['seconds' => \App\Config::INTERVAL_REQUEST_SECONDS,]
                );
                sleep(\App\Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }
        }
    }

    private function addHistory(PostDto $p): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO post_history_feed (posted_at, webhook_id, article_id)
            VALUES (strftime('%s', 'now'), :wid, :aid);"
        );

        $stmt->bindValue(':wid', $p->webhookId, \PDO::PARAM_INT);
        $stmt->bindValue(':aid', $p->articleId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        return $result;
    }
}
