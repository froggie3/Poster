<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\CommandFlags\Flags;
use App\Utils\DiscordPostPoster;
use App\Utils\ClientFactory;
use Monolog\Logger;

class Feed
{
    private \PDO $db;
    private Flags $flags;
    private Logger $logger;
    private array $posts = [];

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
        $this->flags = $flags;
        $this->db = $pdo;
    }

    public function process(): void
    {
        if (!$this->flags->isUpdateSkipped()) {
            $this->retrieveArticles();
        }

        $planner = new PostingPlanner($this->logger, $this->db);
        $this->posts = $planner->fetch();

        if (!empty($this->posts)) {
            $this->logger->info("Processing queue", ['in queue' => count($this->posts)]);
            $this->post();
        }
    }

    private function retrieveArticles(): void
    {
        $feedProviders = [];
        $p = new ProvidersRetriever($this->logger, $this->db, $this->flags);

        if (!empty($providers = $p->fetch())) {
            $this->logger->info('Update needed');

            foreach ($providers as $v) {
                $this->logger->debug('Fetching', ['provider' => $v->getId()]);
                $feedProviders[] = $v->process();
            }
        } else {
            $this->logger->info("There is no feeds to update. ");
            return;
        }

        foreach ($feedProviders as $v) {
            $saver = new FeedSaver($this->logger, $this->db, $v);
            $saver->save();

            $updatedResult = $this->updateFeeds($v->getId());

            if ($updatedResult) {
                $this->logger->debug('Updated the last updated time', [
                    'feedProvider' => $v->getId(),
                    'count' => $v->countArticles(),
                ]);
            }

            $this->logger->debug('Saved article', [
                'feedProvider' => $v->getId(),
                'count' => $v->countArticles(),
            ]);
        }
    }

    /** Updates the table 'feeds' when updated */
    private function updateFeeds(int $feedId)
    {
        $query = "UPDATE feeds SET updated_at = strftime('%s', 'now') WHERE id = :feedId";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue('feedId', $feedId, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function post(): void
    {
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
