<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class SpecValue extends BaseModel
{
    use SoftDelete;
    protected $hidden = ['create_time', 'delete_time', 'update_time'];
}