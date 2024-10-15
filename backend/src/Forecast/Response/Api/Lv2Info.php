<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

class Lv2Info
{
    readonly string $lv2Id;
    readonly string $pid;
    readonly string $name;

    public function __construct(array $data)
    {
        $this->lv2Id = $data['lv2id'];
        $this->pid = $data['pid'];
        $this->name = $data['name'];
    }
}
