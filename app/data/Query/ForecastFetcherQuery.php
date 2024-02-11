<?php

declare(strict_types=1);

namespace App\Data\Query;

class ForecastFetcherQuery
{
    public string $uid;
    public string $kind = 'web';
    public string $akey;

    public function __construct(string $placeId)
    {
        $this->uid = $placeId;
        $this->akey = hash('md5', 'nhk');
    }

    public function buildQuery(): string
    {
        return http_build_query($this);
    }
}
