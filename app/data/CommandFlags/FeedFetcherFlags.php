<?php

declare(strict_types=1);

namespace App\Data\CommandFlags;

class FeedFetcherFlags
{
    protected bool $isForced;
    protected bool $isUpdateSkipped;
    protected string $databasePath;

    public function __construct(
        bool $isForced = false,
        string $databasePath = '',
        bool $isUpdateSkipped = false,
    ) {
        $this->isForced = $isForced;
        $this->isUpdateSkipped = $isUpdateSkipped;
        $this->databasePath = $databasePath;
    }

    public function isForced(): bool
    {
        return $this->isForced;
    }

    public function isUpdateSkipped(): bool
    {
        return $this->isUpdateSkipped;
    }

    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }

    public function setForced(bool $isForced): self
    {
        $this->isForced = $isForced;
        return $this;
    }

    public function setUpdateSkipped(bool $isUpdateSkipped): self
    {
        $this->isUpdateSkipped = $isUpdateSkipped;
        return $this;
    }

    public function setDatabasePath(string $databasePath): self
    {
        $this->databasePath = $databasePath;
        return $this;
    }
}
