<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Database;

class Telop
{
    /**
     * 天気
     * 
     * @var string
     */
    readonly string $distinctName;

    /**
     * 絵文字
     * 
     * @var string
     */
    readonly string $emojiName;

    /**
     * アプリからとってきたテロップのファイル名
     * 
     * @var string
     */
    readonly string $telopFilename;

    /**
     * NHK NEWS WEB のテロップを親であり、-1としたとき、子であるならその親の番号
     * 
     * @var int
     */
    // readonly int $telopIdParent;

    /**
     * 
     * @var TelopImageUsed
     */
    // readonly TelopImageUsed $typeExistsIn;

    public function __construct() {}
}
