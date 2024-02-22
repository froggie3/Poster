<?php

declare(strict_types=1);

namespace App\Interface;

use App\Data\Discord\DiscordPost;

interface DiscordRPGeneratorInterface
{
    public function process(): DiscordPost;
}
