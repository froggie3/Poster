<?php

namespace App\Domain\Forecast\Consumer;

use App\Utils\DtoBase;

class ForecastDto extends DtoBase
{
    public int $webhookId;
    public int $locationId;
    public string $placeId;
    public string $webhookUrl;
    public string $content;

    public function __construct($properties)
    {
        parent::__construct($properties);
    }
}
