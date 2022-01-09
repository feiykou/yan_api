<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerProject extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    public function customer()
    {
        return $this->hasOne('customer','link_code', 'link_code')
            ->bind([
                'customer_name' => 'name'
            ]);
    }

    /**
     * 获取所有分页信息
     * @return array
     */
    public static function getPaginate($UID='', $customerID='')
    {
        $where = [];
        if(isset($customerID) && $customerID && !empty($customerID)) $where['link_code'] = intval($customerID);
        if(isset($UID) && $UID && !empty($UID)) $where['user_id'] = $UID;
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($where)->count();
        $listData = $listData->limit($start, $count)
            ->where($where)
            ->order(['create_time' => 'desc', 'id' => 'desc'])
            ->with('customer')
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
    public static function getDetail($id)
    {
        $where = [
            'id' => $id
        ];
        $result = self::where($where)
            ->find();
        return $result;
    }


    /**
     * 每天跟进客户数统计
     */
    public static function getCustomerFollowByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(update_time,'{$format}') as date,
                        customer_id,
                        count(*) as count")
            ->group('date,customer_id')
            ->select();
        return $customer;
    }
}