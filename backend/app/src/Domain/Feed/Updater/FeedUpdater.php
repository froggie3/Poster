<?php

declare(strict_types=1);

namespace App\Domain\Feed\Updater;

use App\Config;
use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Updater\Website\Website;
use App\Utils\ClientFactory;
use Monolog\Logger;


class FeedUpdater
{
    private \PDO $db;
    private Flags $flags;
    private Logger $logger;
    private ProvidersArray $providers;

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->flags = $flags;
        $this->db = $pdo;
        $this->providers = new ProvidersArray($this->logger, $this->db,);
    }

    public function process(): void
    {
        $this->extractProviders()->retrieveArticles()->saveArticles();
    }

    /**
     * Visit and collect websites' feeds
     */
    private function extractProviders(): ProvidersArray
    {
        try {
            if ($this->flags->isForced()) {
                $this->logger->alert("'--force-update' is set");
            }

            $stmt = $this->db->prepare(
                $this->flags->isForced()
                    ? "SELECT id, url FROM feeds"
                    : "SELECT id, url FROM feeds WHERE strftime('%s', 'now') - updated_at >= :cache"
            );

            if ($stmt) {
                if (!$this->flags->isForced()) {
                    $stmt->bindValue(':cache', Config::FEED_CACHE_LIFETIME, \PDO::PARAM_INT);
                }

                $feedIo = new \FeedIo\FeedIo(new \App\FeedIo\Adapter\Http\Client((new ClientFactory($this->logger))->create()), $this->logger);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $this->providers[] = new Website($feedIo, ...$row, logger: $this->logger,);
                }
            } else {
                throw new \PDOException("Failure on preparing statements");
            }
        } catch (\PDOException $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->providers;
    }
}
