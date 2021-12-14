<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerMain extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['main_address'];

    public function getStatusTextAttr($value,$data)
    {
        var_dump($value);
        var_dump($data);
//        $status = [0=>'禁用',1=>'正常',2=>'待审核'];
//        return $status[$data['status']];
    }

}