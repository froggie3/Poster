<?php

declare(strict_types=1);

namespace App\Data\Discord;

// To be an element of the array 'footer' property in Embed
class Footer extends \App\Utils\DtoBase
{
    public string $text;
    public string $icon_url;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
