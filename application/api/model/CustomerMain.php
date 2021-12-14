<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class Customer extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['address'];

    public function getStatusTextAttr($value,$data)
    {
        var_dump($value);
        var_dump($data);
//        $status = [0=>'禁用',1=>'正常',2=>'待审核'];
//        return $status[$data['status']];
    }

    /**
     * 获取所有分页信息
     * @return array
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public static function getColumnPaginate($uid='',$status=0)
    {
        $where = [];
        if($uid) $where['user_id'] = $uid;
        if(intval($status) == 1) $where['status'] = 1;
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->count();
        $listData = $listData->limit($start, $count)
            ->where($where)
            ->order(['create_time' => 'desc', 'id' => 'desc'])
            ->select();
        $result = [
            // 查询结果
            'collection' => $listData,
            // 总记录数
            'total_nums' => $totalNums
        ];
        return $result;
    }

    /**
     * 获取详情
     */
    public static function getCustomerDetail($id,$delfield='')
    {
        $where = [
            'id' => $id
        ];
        $result = self::where($where)
            ->hidden([$delfield])
            ->find();
        return $result;
    }
}