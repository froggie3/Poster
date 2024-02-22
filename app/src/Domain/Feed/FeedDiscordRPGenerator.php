<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use App\Interface\DiscordRPGeneratorInterface;
use App\Data\Discord\DiscordPost;


class FeedDiscordRPGenerator implements DiscordRPGeneratorInterface
{
    public string $content;

    public function __construct(string $content = "")
    {
        $this->content = $content;
    }

    public function process(): DiscordPost
    {
        return new DiscordPost(["content" => $this->content]);
    }
}
