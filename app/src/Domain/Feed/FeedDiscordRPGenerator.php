<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Interface\DiscordRPGeneratorInterface;
use App\Data\Discord\Card;


class FeedDiscordRPGenerator implements DiscordRPGeneratorInterface
{
    public string $content;

    public function __construct(string $content = "")
    {
        $this->content = $content;
    }

    public function process(): Card
    {
        return new Card(["content" => $this->content]);
    }
}
