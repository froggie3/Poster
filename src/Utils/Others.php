<?php

declare(strict_types=1);

namespace App\Utils;

class Others
{
    /**
     * Gets user input and checks if the input value is within a range [start, end)
     */
    public static function getInputInRange(int $start, int $end): int
    {
        [$input] = sscanf(trim(fgets(STDIN)), "%d\n");
        $continue = true;
        while (!$continue) {
            if (!\is_int($input) or $input < $start or $input >= $end) {
                echo "  INPUT VALUE INCLUDES AN ILLEGAL VALUE!!" . PHP_EOL;
            }
            $continue = false;
        }
        return $input;
    }

    /**
     * Enumerates $choices given.
     */
    public static function displayChoices(array $choices): void
    {
        foreach ($choices as $key => $value) {
            echo "  $key) $value";
        }
    }
}
