<?php

declare(strict_types=1);

namespace App\DataTypes;

use App\DataTypes\TelopImageUsed;

class Telop
{
    # 天気
    public string $distinct_name;

    # 絵文字
    public string $emoji_name;

    # アプリからとってきたテロップのファイル名
    public string $telop_filename;

    # NHK NEWS WEB のテロップを親であり、-1としたとき、子であるならその親の番号
    public int $telop_id_parent;

    public TelopImageUsed $type_exists_in;

    function __construct(
        string    $distinctName,
        string    $emojiName,
        string    $telopFilename,
        int    $telopIdParent,
        TelopImageUsed $typeExistsIn,
    ) {
        $this->distinct_name = $distinctName;
        $this->emoji_name = $emojiName;
        $this->telop_filename = $telopFilename;
        $this->telop_id_parent = $telopIdParent;
        $this->type_exists_in = $typeExistsIn;
    }
}
