<?php

declare(strict_types=1);

namespace App\Domain\Feed\Cache;

class PostsArray extends \ArrayObject
{
    public function needsUpdate()
    {
        return !empty($this);
    }
}
