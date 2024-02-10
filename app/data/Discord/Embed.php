<?php

declare(strict_types=1);

namespace App\Data\Discord;

// To be an element of the array 'embeds' property in RichPresence
class Embed extends RichPresence
{
    public string    $title;
    public string    $description;
    public string    $url;
    public string    $timestamp;
    public int       $color;
    public Thumbnail $thumbnail;
    public Footer    $footer;
    public Author    $author;
    public array     $fields;

    function __construct($properties)
    {
        parent::__construct($properties);
    }
}
