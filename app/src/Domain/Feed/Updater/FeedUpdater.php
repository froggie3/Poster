<?php

declare(strict_types=1);

namespace App\Domain\Feed\Updater;

use App\Data\CommandFlags\Flags;
use App\Domain\Feed\Updater\Retriever\ProvidersRetriever;
use Monolog\Logger;

class FeedUpdater
{
    private \PDO $db;
    private Flags $flags;
    private Logger $logger;
    private WebsiteArray $feedProviders;

    public function __construct(Logger $logger, Flags $flags, \PDO $pdo)
    {
        $this->logger = $logger;
        $this->logger->debug("Feed got ready", ['flags' => (array)$flags]);
        $this->flags = $flags;
        $this->db = $pdo;
        $this->feedProviders = new WebsiteArray();
    }

    public function retrieveArticles(): void
    {
        $p = new ProvidersRetriever(
            $this->logger,
            $this->db,
            $this->flags
        );

        if (!empty($providers = $p->fetch())) {
            $this->logger->info('Update needed');

            foreach ($providers as $v) {
                $this->logger->debug('Fetching', ['provider' => $v->getId()]);
                $feedProviders[] = $v->process();
            }
            $this->updateFeeds();
        } else {
            $this->logger->info("There is no feeds to update.");
            return;
        }
    }

    private function updateFeeds(): void
    {
        foreach ($this->feedProviders as $v) {
            $saver = new FeedSaver($this->logger, $this->db, $v);
            $saver->save();

            $updatedResult = $this->updateFeedsTable($v->getId());

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
    private function updateFeedsTable(int $feedId)
    {
        $query =
            "UPDATE feeds SET updated_at = strftime('%s', 'now')
            WHERE id = :feedId";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue('feedId', $feedId, \PDO::PARAM_INT);

        return $stmt->execute();
    }
}
