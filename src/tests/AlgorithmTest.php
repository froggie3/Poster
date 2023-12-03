#!/usr/bin/env php
<?php

namespace App;

use \App\Utils\Algorithm;
use PHPUnit\Framework\TestCase;

require __DIR__ . "/../../vendor/autoload.php";

final class AlgorithmTest extends TestCase
{
    public function testMemoized(): void
    {
        function fibonacci($n)
        {
            if ($n < 2) {
                return $n;
            } else {
                return fibonacci($n - 1) + fibonacci($n - 2);
            }
        }
        $memoizedFibonacci = Algorithm::memoized('App\fibonacci');

        $memoizedFibonacci(10);
        $this->assertSame($memoizedFibonacci(10), 55);
    }

    public function testClamp(): void
    {
        $this->assertSame(algorithm::clamp(-5, 0, 3), 0);
        $this->assertSame(algorithm::clamp(0, 0, 3), 0);
        $this->assertSame(algorithm::clamp(3, 0, 3), 3);
        $this->assertSame(algorithm::clamp(5, 0, 3), 3);
    }
}
