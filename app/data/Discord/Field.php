<?php

declare(strict_types=1);

namespace App\Data\Discord;

// To be an element of the array 'fields' property in Embed 
class Field  extends \App\Utils\DtoBase
{
    public string $name;
    public string $value;
    public bool   $inline;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
