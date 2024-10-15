<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast;

use PDO;

class MessageHeader
{
    /**
     * 接頭辞になるベースURL
     * 
     * @var string 
     */
    readonly public string $baseUrl;

    /**
     * メッセージ作成者の画像URL
     * 
     * @var string 
     */
    readonly public string $authorUrl;

    /**
     * アバター画像のURL
     * 
     * @var string 
     */
    readonly public string $avatarUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;

        $this->authorUrl = "$this->baseUrl/author.jpg";
        $this->avatarUrl = "$this->baseUrl/avatar.png";
    }

    /**
     * データベースからベースURLを取得して新しいインスタンスを作成する。
     * 
     * @param PDO データベース接続
     * @return MessageHeader MessageHeader.
     */
    static function createFromDB(PDO $pdo): self
    {
        $baseUrl = Utils::getSettingValue($pdo, 'forecast_base_url');
        $class = new self($baseUrl);

        return $class;
    }
}
