<?php

declare(strict_types=1);

namespace App\Interface\ConsumerInterface;

interface AddHistoryInterface
{
    /**
     * Append the record of posts to the database
     */
    public function addHistory(object $object): bool;
}
