<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Database;

class TelopImageUsed
{
    /**
     * Web版に特有のテロップか
     * 
     * @var bool
     */
    public bool $web = false;

    /**
     * アプリ版に特有のテロップか
     * 
     * @var bool
     */
    public bool $app = false;

    public function __construct(bool $web, bool $app)
    {
        $this->web = $web;
        $this->app = $app;
    }
}
