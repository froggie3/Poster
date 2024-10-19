<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api\Lv2Info;

/**
 * 地域情報。
 */
class Lv2Info
{
    readonly string $lv2Id;
    readonly string $pid;
    readonly string $name;

    /**
     * コンストラクタ。
     */
    public function __construct(array $data)
    {
        $this->lv2Id = $data['lv2id'];
        $this->pid = $data['pid'];
        $this->name = $data['name'];
    }
}
