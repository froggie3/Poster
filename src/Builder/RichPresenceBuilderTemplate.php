<?php

declare(strict_types=1);

namespace App\Builder;

use App\DataTypes\Discord\{
    Author,
    Card,
    Embed,
    Field,
    Footer,
    Thumbnail,
};

interface RichPresenceBuilderInterface
{
    public function preparePayload(): Card;
}
