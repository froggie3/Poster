<?php

namespace App\DB;

use SQLite3;

class Database extends SQLite3
{
    function __construct(string $filename)
    {
        $this->open($filename);
    }
}
