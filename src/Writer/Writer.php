<?php

namespace App\Writer;

declare(strict_types=1);

class Writer
{
    public string $filename;
    public string $data;

    function __construct(string $filename, string $data,)
    {
        $this->filename = $filename;
        $this->data = $data;
    }
}
