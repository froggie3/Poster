<?php

declare(strict_types=1);

namespace App\Data\Discord;

class RichPresence
{
    function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }
}
