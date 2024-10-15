<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast;

use PDO;

class MessageHeader
{
    /**
     * ファイル名などの接頭辞として添加されるベース URL。
     *
     * @var string
     */
    readonly public string $baseUrl;

    /**
     * メッセージ作成者の画像 URL。
     *
     * @var string
     */
    readonly public string $authorUrl;

    /**
     * メッセージ作成者としてメッセージの左側に表示されるアバター画像の URL
     *
     * @var string
     */
    readonly public string $avatarUrl;

    /**
     * コンストラクタ
     *
     * @param string $baseUrl
     */
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
