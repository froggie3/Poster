<?php

declare(strict_types=1);

namespace App\Data\Discord;

// To be an element of the array 'author' property in Embed
class Author  extends \App\Utils\DtoBase
{
    public string $name;
    public string $url;
    public string $icon_url;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
