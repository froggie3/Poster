<?php

declare(strict_types=1);

namespace Iigau\Poster;

class Constants
{
    // 環境変数として設定する変数のキー名
    const WEBHOOK_URL_KEY = 'WEBHOOK_URL';
    const PLACE_ID_KEY = 'PLACE_ID';

    // command-line flags and parameters
    const FLAG_FORCE_UPDATE = 'force-update';
    const FLAG_NO_UPDATE_CHECK = 'no-update-check';
    const PARAM_DATABASE_PATH = 'database-path';
}
