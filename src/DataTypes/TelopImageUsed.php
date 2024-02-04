<?php

declare(strict_types=1);

namespace App\DataTypes;


class TelopImageUsed
{
    # Web版に特有のテロップか
    public bool $web = false;

    # アプリ版に特有のテロップか
    public bool $app = false;

    function __construct(bool $web, bool $app)
    {
        $this->web = $web;
        $this->app = $app;
    }
}
