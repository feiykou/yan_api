<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerLog extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['address', 'img_urls'];

    // 设置spu_detail_img_list多图片链接
    protected function getImgUrlsAttr($value)
    {
        if(!$value) return;
        return $this->setMultiImgPrefix($value);
    }

    // 设置spu_detail_img_list多图片链接
    protected function setImgUrlsAttr(array $value)
    {
        return $this->cancelMultiImgPrefix($value);
    }

    protected function getContentAttr($value) {
        return rawurldecode($value);
    }

    public function getStatusTextAttr($value,$data)
    {
        var_dump($value);
        var_dump($data);
//        $status = [0=>'禁用',1=>'正常',2=>'待审核'];
//        return $status[$data['status']];
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerAdd()
    {
        return $this->hasOne('customer_add', 'user_id', 'id');
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerMain()
    {
        return $this->hasOne('customer_main', 'user_id', 'id');
    }



    /**
     * 获取所有分页信息
     * @return array
     */
    public static function getPaginate($uid='',$status=0)
    {
        $where = [];
        if($uid) $where['customer_id'] = $uid;
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($where)->count();
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