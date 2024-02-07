<?php

declare(strict_types=1);

namespace App\DataTypes\Discord;

// To be an element of the array 'fields' property in Embed 
class Field  extends RichPresence
{
    public string $name;
    public string $value;
    public bool   $inline;

    function __construct($properties)
    {
        parent::__construct($properties);
    }
}
