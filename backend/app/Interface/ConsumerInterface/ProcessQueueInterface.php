<?php

declare(strict_types=1);

namespace App\Interface\ConsumerInterface;

interface ProcessQueueInterface
{
    /**
     * Process queue given as an argument. Returns false if fails.
     */
    public function process(): void;
}
