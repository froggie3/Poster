<?php

declare(strict_types=1);

namespace App\Domain\Feed\Updater;

use App\Domain\Feed\Updater\Website\Article;
use App\Domain\Feed\Updater\Website\Website;
use Monolog\Logger;

/**
 * A representation of the set of class Websites, with some functionalities to itself.
 */
class ProvidersArray extends \ArrayObject
{
    private \PDO $db;
    private Logger $logger;

    public function __construct(Logger $logger, \PDO $db,)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Returns true if there is any website needs updated
     */
    public function needsUpdate(): bool
    {
        return !empty($this);
    }

    public function retrieveArticles(): self
    {
        $processed = new ProvidersArray($this->logger, $this->db);

        if (!$this->needsUpdate()) {
            return $processed;
        }

        $this->logger->info('Update needed');
        foreach ($this as $provider) {
            assert($provider instanceof Website);
            $this->logger->debug('Fetching', ['provider' => $provider->getId()]);
            $processed[] = $provider->process();
        }

        return $processed;
    }

    public function saveArticles(): void
    {
        if (!$this->needsUpdate()) {
            $this->logger->info("There is no feeds to update.");
            return;
        }

        $this->db->beginTransaction();

        foreach ($this as $provider) {
            assert($provider instanceof Website);

            foreach ($provider->getArticles() as $article) {
                assert($article instanceof Article);
                // Do nothing when there is an existing article with the same URL
                $stmt = $this->db->prepare("INSERT OR IGNORE INTO articles (title, url, feed_id, updated_at) VALUES (:title, :url, :feedId, :updatedAt);");
                $stmt->bindvalue(':title', $article->title, \PDO::PARAM_STR,);
                $stmt->bindvalue(':url', $article->link, \PDO::PARAM_STR,);
                $stmt->bindvalue(':updatedAt', $article->updatedAt->getTimestamp(), \PDO::PARAM_INT,);
                $stmt->bindvalue(':feedId', $provider->getId(), \PDO::PARAM_INT,);
                $stmt->execute();
            }

            // Updates the column updated_at on 'feeds' when updated
            $stmt = $this->db->prepare("UPDATE feeds SET updated_at = strftime('%s', 'now') WHERE id = :feedId");
            $stmt->bindValue('feedId', $provider->getId(), \PDO::PARAM_INT);
            $stmt->execute();

            $this->logger->debug('Saved article', ['feedProvider' => $provider->getId(), 'count' => $provider->countArticles(),]);
        }

        $this->db->commit();
    }
}
