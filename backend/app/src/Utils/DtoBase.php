<?php

declare(strict_types=1);

namespace App\Utils;

class DtoBase
{
    public function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }
}
