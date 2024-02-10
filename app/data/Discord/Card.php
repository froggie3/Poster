<?php

declare(strict_types=1);

namespace App\Data\Discord;

class Card extends RichPresence
{
    public string $content;
    public string $username;
    public string $avatar_url;
    public array  $embeds = [];

    function __construct($properties)
    {
        parent::__construct($properties);
    }
}
