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
     * 客户日志一对多
     * @return \think\model\relation\HasMany
     */
    public function customerLog()
    {
        return $this->hasMany('customer_log', 'customer_id', 'id');
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerMain()
    {
        return $this->hasOne('customer_main', 'link_code', 'link_code');
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerProject()
    {
        return $this->hasMany('customer_project', 'link_code', 'link_code');
    }


    /**
     * 获取所有分页信息
     * @return array
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public static function getCustomerPaginate($uid='',$params=[])
    {
        $field = ['name', 'follow_status', 'user_code', 'author'];
        $query = self::equalQuery($field, $params);
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        if(!empty($query)) {
            foreach ($query as $key => $val) {
                if(isset($val) && empty($val)) {
                    unset($query[$key]);
                }
            }
        }
        if(empty($query)) $query = [];
        if($uid && $uid > 0) {
            $query[] = ['user_id','=',$uid];
        } else {
            if($uid == -1) {
                // 释放的客户
                $query[] = ['user_id','<>',0];
            } else {
                $query[] = ['user_id','=',0];
            }
        }
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($query)->count();
        $listData = $listData->limit($start, $count)
            ->where($query)
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
            ->with(['customerMain'])
            ->find();
        return $result;
    }

    /**
     * 新客超3天未跟进
     */
    public static function passNoFollowData($uid = '')
    {
        // 获取3天前时间
        $startTime = date('Y-m-d');
        $startTime = strtotime($startTime);
        $noFollowTime = date('Y-m-d',strtotime("-3 day", $startTime));
        $query = [];
        $query[] = ['follow_time', '<', $noFollowTime];
        if(isset($uid) && $uid > 0) {
           $query[] = ['user_id','=',$uid];
        } else{
           $query[] = ['user_id','<>',0];
        }
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($query)->count();
        $listData = $listData->limit($start, $count)
            ->where($query)
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
     * 更新跟进时间
     */
    public static function updateFollowTime($customer_id = 0)
    {
        $curTime = (new self())->formatDateTime('Y-m-d H:i:s.u');
        $result = self::where('id', $customer_id)
            ->update(['follow_time' => $curTime]);
        return $result;
    }


    /**
     * 未跟进客户数
     *  3-5天  6-8天  9-11天  12-14天
     */
    public static function getNoFollowByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(follow_time,'{$format}') as date,
                        count(*) as count")
            ->group('date')
            ->select();
        return $customer;
    }

    /**
     * 获取用户数据分析数据
     * @param $params
     * @param $format
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCustomerStatisticsByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(create_time,'{$format}') as date,
                        count(*) as count")
            ->group('date')
            ->select();
        return $customer;
    }

    /**
     * 获取各渠道来源客户数
     */
    public static function getCustomerChannelByDate()
    {
        $query = [];
        $user = self::where($query)
            ->field("channel,count(*) as count")
            ->group('channel')
            ->select();
        return $user;
    }

    /**
     * 总客户数 统计
     */
    public static function totalCustomerNum()
    {
        $titalNum = self::field("count(*) as count")->select();
    }

    public static function getCustomerAndProject($ids=[])
    {
        $result = self::with(['customerMain', 'customerProject'])
            ->order('id', 'desc')
            ->all($ids);
        return $result;
    }

    /**
     * 根据日志跟进状态更新客户状态
     */
    public static function updateCustomerStatus($customer_id, $status='')
    {
        $result = self::where(['id'=>$customer_id])->update([
            'follow_status' => $status
        ]);
        return $result;
    }

}