<?php

namespace App\DB;

use SQLite3;

class Database extends SQLite3
{
    public function __construct(string $filename)
    {
        $this->open($filename);
    }
}
