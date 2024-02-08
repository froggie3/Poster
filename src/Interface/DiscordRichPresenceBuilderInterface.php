<?php

declare(strict_types=1);

namespace App\Interface;

use App\DataTypes\Discord\Card;

interface DiscordRichPresenceBuilderInterface
{
    public function preparePayload(): Card;
}
