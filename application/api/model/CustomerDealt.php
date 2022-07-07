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


}