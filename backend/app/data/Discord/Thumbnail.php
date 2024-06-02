<?php

declare(strict_types=1);

namespace App\Data\Discord;

// To be an element of the array 'thumbnail' property in Embed
class Thumbnail  extends \App\Utils\DtoBase
{
    public string $url;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
