<?php

declare(strict_types=1);

namespace Iigau\Poster;

class Constants
{
    /**
     * キャッシュを無効化し、強制的に更新するコマンドラインフラグ
     * 
     * @var string
     */
    const FLAG_FORCE_UPDATE = 'force-update';

    /**
     * データベースを指定するためのコマンドラインフラグ
     * 
     * @var string
     */
    const PARAM_DATABASE_PATH = 'database-path';
}
