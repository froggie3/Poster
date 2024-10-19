<?php

declare(strict_types=1);

namespace Iigau\Poster\Forecast\Response\Api;

use Iigau\Poster\Forecast\Response\Api\BlockInfo\BlockInfo;
use Iigau\Poster\Forecast\Response\Api\Kafun\Kafun;
use Iigau\Poster\Forecast\Response\Api\Lv2Info\Lv2Info;
use Iigau\Poster\Forecast\Response\Api\Mrf\Mrf;
use Iigau\Poster\Forecast\Response\Api\Srf\Srf;
use Iigau\Poster\Forecast\Response\Api\Trf\Trf;

/**
 * `/v1/lv3/wx` のJSONレスポンスをクラス表現としたもの
 */
class WeatherForecast
{
    readonly Srf $srf;
    readonly Lv2Info $lv2Info;
    readonly BlockInfo $blockInfo;
    readonly string $uid;
    readonly Mrf $mrf;
    readonly string $name;
    readonly string $createdDate;
    readonly Kafun $kafun;
    readonly Trf $trf;

    /**
     * コンストラクタ
     */
    public function __construct(array $data)
    {
        $this->srf = new Srf($data['srf']);
        $this->lv2Info = new Lv2Info($data['lv2_info']);
        $this->blockInfo = new BlockInfo($data['block_info']);
        $this->uid = $data['uid'];
        $this->mrf = new Mrf($data['mrf']);
        $this->name = $data['name'];
        $this->createdDate = $data['created_date'];
        $this->kafun = new Kafun($data['kafun']);
        $this->trf = new Trf($data['trf']);
    }

    /**
     * JSONレスポンスからインスタンスを作成するためのメソッド
     */
    static function fromJson(string $content): self
    {
        $data = json_decode($content, true, JSON_THROW_ON_ERROR);

        return new self($data);
    }
}
