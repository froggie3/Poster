<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class BlockInfo
{
    readonly string $bid;
    readonly string $name;

    public function __construct(array $data)
    {
        $this->bid = $data['bid'];
        $this->name = $data['name'];
    }
}
