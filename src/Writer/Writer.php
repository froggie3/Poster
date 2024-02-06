<?php

namespace App\Writer;

declare(strict_types=1);

class XMLWriter extends Writer
{
    public function write(): bool
    {
        $fp = fopen($this->filename, 'w');
        if (fwrite($fp, $this->data)) {
            echo 'success';
            return true;
        }
        throw new ('Failed to write XML Document');
    }
}

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
