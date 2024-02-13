<?php

namespace App\Domain\Feed;

use App\Utils\DtoBase;

class PostDto extends DtoBase
{
    public int $articleId;
    public string $articleTitle;
    public string $articleUrl;
    public int $webhookId;
    public string $webhookTitle;
    public string $webhookUrl;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
