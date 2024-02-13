<?php

declare(strict_types=1);

namespace App\Data\Discord;

class Card extends \App\Utils\DtoBase
{
    public string $content;
    public string $username;
    public string $avatar_url;
    public array  $embeds = [];

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
