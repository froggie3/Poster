<?php

declare(strict_types=1);

namespace App\Data;


class TelopImageUsed
{
    # Web版に特有のテロップか
    public bool $web = false;

    # アプリ版に特有のテロップか
    public bool $app = false;

    public function __construct(bool $web, bool $app)
    {
        $this->web = $web;
        $this->app = $app;
    }
}
