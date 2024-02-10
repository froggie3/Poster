<?php

declare(strict_types=1);

namespace App\DataTypes\Discord;

// To be an element of the array 'footer' property in Embed
class Footer extends RichPresence
{
    public string $text;
    public string $icon_url;

    function __construct($properties)
    {
        parent::__construct($properties);
    }
}
