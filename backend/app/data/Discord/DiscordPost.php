<?php

declare(strict_types=1);

namespace App\Data\Discord;

class DiscordPost extends \App\Utils\DtoBase
{
    public string $content;
    public string $username;
    public string $avatar_url;
    public array  $embeds = [];

    public function __construct($properties)
    {
        parent::__construct($properties);
    }

    public function toJson(): string
    {
        return json_encode($this);
    }
}
