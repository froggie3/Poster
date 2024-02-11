<?php

declare(strict_types=1);

namespace App\Data\CommandFlags;

class FeedFetcherFlags
{
    protected bool $isForced;
    protected string $databasePath;

    public function __construct(bool $isForced = false, string $databasePath = '')
    {
        $this->isForced = $isForced;
        $this->databasePath = $databasePath;
    }

    public function isForced(): bool
    {
        return $this->isForced;
    }

    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }

    public function setForced(bool $isForced): self
    {
        $this->isForced = $isForced;
        return new self;
    }

    public function setDatabasePath(string $databasePath): self
    {
        $this->databasePath = $databasePath;
        return new self;
    }
}
