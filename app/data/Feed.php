<?php

declare(strict_types=1);

namespace App\Data;


class Feed
{
    public int $dest_id;
    public array $content;

    public function __construct(int $dest_id, array $content)
    {
        $this->dest_id = $dest_id;
        $this->content = $content;
    }
}
