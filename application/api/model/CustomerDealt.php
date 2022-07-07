<?php


namespace app\api\model;


use think\Db;
use think\Exception;
use think\model\concern\SoftDelete;

class CustomerDealt extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['img_urls'];
    // 设置spu_detail_img_list多图片链接
    protected function getImgUrlsAttr($value)
    {
        if(!$value) return;
        return $this->setMultiImgPrefix($value);
    }

}