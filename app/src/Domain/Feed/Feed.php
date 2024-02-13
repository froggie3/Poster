<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Data\CommandFlags\FeedFetcherFlags;
use App\DB\Database;
use App\Utils\CardPoster;
use App\Utils\ClientFactory;
use Monolog\Logger;


class Feed
{
    private Database $db;
    private FeedFetcherFlags $flags;
    private Logger $logger;
    private array $posts = [];

    public function __construct(Logger $logger, FeedFetcherFlags $flags)
    {
        $this->flags = $flags;
        $this->logger = $logger;
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
        $this->logger = $logger;
        $this->setDatabaseReady();
    }

    private function retrieveArticles(): void
    {
        $feedProviders = [];
        $p = new ProvidersRetriever($this->logger, $this->db, $this->flags);

        if (!empty($providers = $p->fetch())) {
            $this->logger->info('Update needed');
            foreach ($providers as $v) {
                $this->logger->info('Fetching', ['provider' => $v->getId()]);
                $feedProviders[] = $v->process();
            }
        } else {
            $this->logger->info('Already up-to-date');
            return;
        }

        foreach ($feedProviders as $v) {
            $saver = new FeedSaver($this->logger, $this->db, $v);
            $saver->save();
            $this->logger->debug('Saved article', ['feedProvider' => $v->getId(), 'count' => $v->countArticles(),]);
        }
    }

    public function process(): void
    {
        if (!$this->flags->isUpdateSkipped()) {
            $this->retrieveArticles();
        }

        $planner = new PostingPlanner($this->logger, $this->db);
        $this->posts = $planner->fetch();

        print_r($this->posts);
        $this->post();
    }


    private function post(): void
    {
        $this->logger->debug("Processing queue", ['in queue' => count($this->posts)]);

        while ($p = array_pop($this->posts)) {
            $content = "$p->articleTitle\n$p->articleUrl";
            $builder = new FeedDiscordRPGenerator($content);

            $card = $builder->process();

            $cp = new CardPoster(
                $this->logger,
                (new ClientFactory($this->logger, [
                    'Content-Type' => 'application/json'
                ]))->create(),
                $card,
                $p->webhookUrl
            );
            $cp->post();
            $this->addHistory($p);

            $this->logger->debug("Success", ['in queue' => count($this->posts)]);

            if (!empty($this->posts)) {
                $this->logger->debug(
                    "Waiting for the next request",
                    ['seconds' => \App\Config::INTERVAL_REQUEST_SECONDS,]
                );
                //sleep(\App\Config::INTERVAL_REQUEST_SECONDS);
            } else {
                break;
            }
        }
    }

    private function addHistory(PostDto $p)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO post_history (post_date, webhook_id, article_id, location_id, source_id)
            VALUES (strftime('%s', 'now'), :wid, :aid, NULL, 2)"
        );

        $stmt->bindValue(':wid', $p->webhookId, SQLITE3_INTEGER);
        $stmt->bindValue(':aid', $p->articleId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        return $result;
    }

    private function setDatabaseReady(): void
    {
        $flag = true;
        $databasePath = __DIR__ . '/../../../../sqlite.db';
        if (!empty($this->flags->getDatabasePath()))
            $databasePath = $this->flags->getDatabasePath();
        try {
            if (!$flag = file_exists($databasePath)) {
                $this->logger->warning("Database not found", ['path' => $databasePath],);
            }
        } catch (\SQLite3Exception $e) {
            $this->logger->error($e->getMessage());
            exit;
        }
        $this->db = new Database($databasePath);
        if (!$flag) {
            $this->logger->info("Created database", ['path' => $databasePath]);
        }
    }
}
